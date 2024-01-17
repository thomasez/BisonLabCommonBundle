<?php

namespace BisonLab\CommonBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use JMS\Serializer\SerializationContext;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

use Symfony\Component\Serializer\Encoder\XmlEncoder;

trait RestTrait
{
    /* 
     * I really wish there were a more or less standardized object for this.
     * The closest without being too complex is Jsend.
     * http://labs.omniti.com/labs/jsend
     * Not really sure I agree with it, but it's close enough. 
     * I will add a "meta" tag, where I can put a json shema or links or other
     * stuff if I need it.
     * 
     * TODO: Switch to this "standard"
     */

    /*
     * Basically, "rest" is the basic authen Web services, "ajax" is the 
     * same but from a web client with session data.
     *
     * I have not found any way to use two different firewalls on the
     * same path, alas, rest and ajax is the same, but different.
     *
     * It's also possible to argue that the Accept headers should decide.
     * But that seems to not work properly in all situations. And if it's REST
     * we return JSON by default, othervise HTML.
     */
     /*
      * TODO: Investigate changing this to find the web/rest access from
      * within the request.
      * And this is how stupendously simple it is:
      * $request->get('access');
      */
    public function isRest($check)
    {
        if ($check instanceof Request)
            $access = $check->get('access');
        else
            $access = $check;

        if ('rest' == $access || 'ajax' == $access) {
            return true;
        } else {
            return false;
        }
    }

    public function returnRestData($request, $data, $templates = array(), $status_code = 200)
    {
        // If the data has a toArray, I would consider it as wanted to be used
        // instead of the jms serializer graph stuff.
        // data can be both an array of objects and one object, aka test.
        /* I think I changed my mind. I'd rather want the programmer/user to
         * decide, not add magic like this. So, you'd better do the toArray
         * conversion before calling this function if you want it like that.
         * (Comment kept for reminding myself and others on the descision)
         */

        /* Accept headers works in mysterious ways, or rather the odd ones
         * creating them. This is some examples: (the asterix/asterix at the
         * end is removed, but all browsers seems to have it.)

Chromium, Linux:

 text/html,application/xhtml+xml,application/xml;q=0.9,image/webp

 [0] => text/html    [1] => application/xhtml+xml    [2] => image/webp    [3] => application/xml

Firefox, Windows:

 text/html,application/xhtml+xml,application/xml;q=0.9

 [0] => text/html    [1] => application/xhtml+xml    [2] => application/xml

Edge, Windows

 text/html, application/xhtml+xml, image/jxr

 [0] => text/html    [1] => application/xhtml+xml    [2] => image/jxr

*/
        foreach ($request->getAcceptableContentTypes() as $accept) {

            switch ($accept) {
                case 'application/json':
                    return $this->returnAsJson($request, $data, $status_code);

                /* JSONP */
                case 'application/javascript':
                    return $this->returnAsJson($request, $data, $status_code);

                case 'application/xml':
                    return $this->returnAsXml($request, $data, $status_code);

                case 'application/yml':
                    return $this->returnAsYaml($request, $data, $status_code);

                case 'text/html':
                case '*/*':
                case 'application/html':
                    $headers["Content-Type"] = $accept;
                    if (isset($templates['html'])) {
                        // Here we'll let the programmer choose.
                        return $this->render($templates['html'],
                            array(
                                // That name is so wrong, but can I remove it?
                                'data_entity' => $data,
                                'data' => $data,
                                'status_code' => $status_code,
                                ),
                                new Response('', $status_code)
                                );
                    } else {
                        // And a fall back.
                        // Reason for this is the extremely simple template for
                        // showing whatever as HTML. Just send it as an array and
                        // it can be dumped
                        // more easily.
                        $data_arr = json_decode($this->_serialize($data, 'json'), true);
                        return $this->render('@BisonLabCommon/Default/show.html.twig', 
                            array('data' => $data_arr),
                            new Response('', $status_code)
                            );
                    }
                case 'text/plain':
                    // Can only send pure string/text.
                    if (is_string($data)) {
                        $headers["Content-Type"] = "text/plain";
                        return new Response($data, $status_code, $headers);
                    } else {
                        $headers["Content-Type"] = "text/plain";
                        return new Response('', $status_code, $headers);
                    }
                    break;
            }
        }
        throw new \InvalidArgumentException("No data returned because of no matching Accept header.");
    }

    /* This is more or less a hack.
     * It's only used when DataTables is in serverSide mode.
     * http://datatables.net/manual/server-side
     * (DataTables themselves menas there is no need for this until er are
     * talking 10K rows. Before that it's be better to push everything and let
     * the client side handle the sorting, paging and filtering.
     */
    public function returnAsDataTablesJson($request, $data, $records_filtered = null, $total_amount = null) 
    {
        $content_arr = array(
            'draw' => $request->get('draw'),
            // Cheating.
            'recordsTotal' => $total_amount != null ? $total_amount : count($data),
            'recordsFiltered' => $records_filtered != null ? $records_filtered : count($data),
            'data' => $data
        );
        $content = $this->_serialize($content_arr, 'json');
        $headers = array();

        if ($request->get('callback')) { 
            $headers["Content-Type"] = "application/javascript";
            $content = $request->get('callback') . "(" . $content . ");";
        } else {
            $headers["Content-Type"] = "application/json";
        }
        $response = new Response($content, 200, $headers);
        return $response;
    }

    public function returnAsJson($request, $data, $status_code = 200) 
    {
        if ($request->get('draw'))
            return $this->returnAsDataTablesJson($request, $data);

        $content = $this->_serialize($data, 'json');

        $headers = array();

        if ($request->get('callback')) { 
            $headers["Content-Type"] = "application/javascript";
            $content = $request->get('callback') . "(" . $content . ");";
        } else {
            $headers["Content-Type"] = "application/json";
        }
        $response = new Response($content, $status_code, $headers);
        return $response;
    }

    public function returnAsXml($request, $data, $status_code = 200) 
    {
        $headers["Content-Type"] = "application/xml";
        $content .= $this->_serialize($data, 'xml');
        return new Response($content, $status_code, $headers);
    }

    public function returnAsYaml($request, $data, $status_code = 200) 
    {
        $headers["Content-Type"] = "text/yaml";
        $content .= $this->_serialize($data, 'yml');
        return new Response($content, $status_code, $headers);
    }

    /* 
     * Jsend:
     *
     * success:
     *   All is A-OK.
     * Returns: 
     *    status - Set to "success")
     *    data   - The data to return.
     */
    public function returnSuccess($request, $data = null, $code = 200) 
    {
        $jsend = array(
            'status' => 'success',
            'data' => $data,
            'code' => $code
        );
        return $this->returnRestData($request, $jsend, array(), $code);
    }

    /* 
     * Jsend:
     *
     * fail:
     *      There was a problem with the data submitted, or some pre-condition
     *      of the API call wasn't satisfied
     * Returns: 
     *    status (Set as "fail")
     *    data ( Validation errors )
     */
    public function returnFail($request, $data = null, $code = 400) 
    {
        $jsend = array(
            'status' => 'fail',
            'data' => $data
        );
        return $this->returnRestData($request, $jsend, array(), $code);
    }

    /* 
     * Jsend:
     *
     * Required keys:
     *   status: Should always be set to "error".
     *   message: A meaningful, end-user-readable (or at the least log-worthy)
     *            message, explaining what went wrong. 
     * 
     * Optional keys:
     *   code: A numeric code corresponding to the error, if applicable
     *   data: A generic container for any other information about the error, 
     *         i.e. the conditions that caused the error, stack traces, etc. 
     */
    public function returnError($request, $message = '', $code = 500) 
    {
        $jsend = array(
            'status' => 'error',
            'message' => $message,
            'code' => $code
        );
        return $this->returnRestData($request, $jsend, ['html' => '@BisonLabCommon/error.html.twig'], $code);
    }

    /* 
     * Problem here? No request object I can use to find the Accept headers and
     * so on. This function should *not* be used. Use returnError or returnFail
     * instead.
     * Or it could be renamed to "returnRestError"
     */
    public function returnErrorResponse($message, $code, $errors = null) 
    {
        $msg = array('code' => $code, 'message' => $message);
        if (is_array($errors)) {
            $msg['errors'] = $errors;
        } elseif ($errors) {
            $msg['errors'] = array($errors);
        }
        return new Response(json_encode($msg), $code);
    }

    /*
     * Grabbing validation errors isn't as simple as I believe it should.
     * (On top of the fact that it's not always validating..)
     * This extracts the errors and puts them in an array with the field as key.
     */
    public function handleFormErrors(&$form)
    {
        $errors = array();
        foreach ($form->getErrors(true, true) as $e) {
            $fieldname = (string)$e->getOrigin()->getName();
            if (!isset($errors[$fieldname])) 
                $errors[$fieldname] = array();
            $errors[$fieldname][] = (string)$e->getMessage();
        }
        return $errors;
    }

    private function _serialize($data, $format)
    {
        if (is_object($data) && method_exists($data, '__toArray')) {
            $serialized = $data->__toArray();
        } else {
            if ($this->serializer ?? null) {
                $serialized = $this->serializer->serialize($data, $format);
            } elseif ($this->jmsSerializer ?? null) {
                $serialized = $this->jmsSerializer->serialize($data, $format, SerializationContext::create()->enableMaxDepthChecks());
            } else {
                throw new \Exception("No serializer found or configured.");
            }
        }
        return $serialized;
    }
}
