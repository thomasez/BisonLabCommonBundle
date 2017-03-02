<?php

namespace BisonLab\CommonBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;

use Symfony\Component\Serializer\Encoder\XmlEncoder;

class CommonController extends Controller
{

    /*
     * The Context stuff
     */

    public function contextGetAction(Request $request, $context_config, $access, $system, $object_name, $external_id)
    {
        $class = $context_config['entity'];
        $em = $this->getDoctrine()->getManagerForClass($class);

        $repo = $em->getRepository($class);

        $entities = $repo->findByContext($system, $object_name, $external_id);

        if ($this->isRest($access)) {
            return $this->returnRestData($request, $entities, 
                array('html' => $context_config['list_template']));
        }

        if (!$entities) {
            return $this->render('BisonLabCommonBundle::message.html.twig', 
                array('message' => 'Sorry, could not find what you were looking for'));
        }

        if (count($entities) == 1) {
            // Need to do this for BC.
            $eid = is_array($entities) 
                ? $entities[0]->getId() 
                : $entities->getId();

            $classMethod = new \ReflectionMethod($this,"showAction");
            $argumentCount = count($classMethod->getParameters());
            if ($argumentCount == 3)
                // There is always a showAction and it's better to use that one
                // than just rendering a template.
                return $this->showAction($request, $access, $eid);
            else
                return $this->showAction($access, $eid);
        } else {
            // Should do that here aswell, but it's a tad longer story.
            // (No action taking a list, they tend to create the list
            // themsewlves.)
            return $this->render($context_config['list_template'],
                array('entities' => $entities));
        }
    }

    public function contextPostAction(Request $request, $context_config, $access)
    {
        $post_data = $request->request->get('form');

        list( $system, $object_name) = explode("__", $post_data['system__object_name']);
        $object_id = $post_data['object_id'];

        return $this->contextGetAction($request, $context_config, $access, $system, $object_name, $object_id);
    }

    public function createContextForms($context_for, $contexts)
    {
        // prepare the contexts, which is putting them in an array we can use
        $context_arr = array();
        foreach ($contexts as $c) {
            $context_arr[$c->getSystem()][$c->getObjectName()] = $c;
        }

        $form_factory = $this->container->get('form.factory');

        // This is actually very wrong... It's not really common is it?
        // TODO: get rid of this one, aka make it generic.
        $context_conf = $this->container->getParameter('app.contexts');
        list($bundle, $object) = explode(":", $context_for);
        $conf = $context_conf[$bundle][$object];
        $forms = array();

        // There  might be no contexts at all.
        if (!$conf) return $forms;

        foreach ($conf as $system_name => $object_info) {
            foreach ($object_info as $context_object_config) {
                $object_name = $context_object_config['object_name'];
                $form_name  = "context__" . $system_name . "__" . $object_name;
                $form_label = $context_object_config['label'];

                if (isset($context_arr[$system_name][$object_name])) {
                    $c_object = $context_arr[$system_name][$object_name];

                    $form   = $form_factory->createNamedBuilder($form_name, FormType::class, $c_object)
                        ->add('id', HiddenType::class, array('data' => $c_object->getId()))
                        ->add('external_id', TextType::class, array('label' => 'External ID', 'required' => false));
                } else {
                    $form   = $form_factory->createNamedBuilder($form_name, FormType::class)
                        ->add('external_id', TextType::class, array('label' => 'External ID', 'required' => false));
                }

                /* Only these two methods shall make it possible to edit/add a
                 * URL in the forms. The rest will be calculated
                 * automatically.*/
                if (!isset($context_object_config['url_from_method'])) {
                    error_log("No url_from_method for " . $systen_name . "::" . $object_name);
                } else {
                    if ($context_object_config['url_from_method'] == "manual" 
                      || $context_object_config['url_from_method'] == "editable") {
                        $form->add('url', TextType::class, 
                            array('label' => 'URL', 'required' => false));
                    }
                }
                $forms[] = array('label' => $form_label,
                        'form' => $form->getForm()->createView());
            }
        } 
        return $forms;
    }

    public function updateContextForms($request, $context_for, $context_class, $owner) {
        $em = $this->getDoctrine()->getManagerForClass($context_for);

        $context_conf = $this->container->getParameter('app.contexts');
        list($bundle, $object) = explode(":", $context_for);
        $conf = $context_conf[$bundle][$object];
        $forms = array();
        // There  might be no contexts at all.
        if (!$conf) return $forms;
        // Object_info was a bas choice, it's the context object listing per
        // system.
        foreach ($conf as $system_name => $object_info) {
            // And here, context_object_config is the object config itself.
            foreach ($object_info as $context_object_config) {
                $object_name = $context_object_config['object_name'];
                $form_name  = "context__" . $system_name . "__" . $object_name;

                $context_arr = $request->request->get($form_name);

                if (empty($context_arr)) { continue; }

                if (isset($context_arr['id']) ) {
                    $context = $em->getRepository($context_class)->find($context_arr['id']);
                    if (empty($context_arr['external_id'])) { 
                        // No need for an empty context.
                        $em->remove($context);
                    } else {
                        $context->setExternalId($context_arr['external_id']);
                    if (empty($context_arr['url']) ) {
                        $context->setUrl(self::createContextUrl($context_arr, $context_object_config));
                    } else {
                        $context->setUrl($context_arr['url']);
                    }
                        $em->persist($context);
                    }
                } elseif (!empty($context_arr['external_id'])) { 
                    $context = new $context_class;
                    $context->setSystem($system_name);
                    $context->setObjectName($object_name);
                    $context->setExternalId($context_arr['external_id']);
                    if (empty($context_arr['url'])) {
                        $context->setUrl(self::createContextUrl($context_arr, $context_object_config));
                    } else {
                        $context->setUrl($context_arr['url']);
                    }
                    $context->setOwner($owner);
                    $owner->addContext($context);
                    $em->persist($context);
                    $em->persist($owner);
                } else {
                    continue;
                }
            }
        }
    }

    static function createContextUrl($context_arr, $config)
    {
        // Good old one.
        if (isset($config['url_base'])) {
            return $config['url_base'] . $context_arr['external_id'];
        }    
        // Or we have a twig template'ish.
        if (isset($config['url_template'])) {
            $url = $config['url_template'];
            foreach ($context_arr as $key => $val) {
                $url = preg_replace('/\{\{\s?'.$key.'\s?\}\}/i',
                                $val , $url);
            }
            return $url;
        }    
    }
    
    public function createContextSearchForm($config, $options = array())
    {
        $choices = array();
        // There  might be no contexts at all.
        if (!$config) return null;
        foreach ($config as $system => $system_config) {
            if (count($system_config) > 1) {
                foreach ($system_config as $object_config) {
                    $choices[ucfirst($system) . " - " . $object_config['object_name']] = $system . "__" .  $object_config['object_name'];
                }
            } else {
                $choices[ucfirst($system)] = $system . "__" .  $system_config[0]['object_name'];
            }
        }

        $form =  $this->createFormBuilder()
            ->add('system__object_name', ChoiceType::class, array('choices' => $choices, 'label' => 'System'))
            ->add('object_id', TextType::class, array('label' => 'ID'))
            ->add('submit', SubmitType::class, array('label' => "Search"));

        if (isset($options['action']))
            $form->setAction($options['action']);
        if (isset($options['method']))
            $form->setMethod($options['method']);

        return $form->getForm();
    }

    /*
     * The REST stuff
     */

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
      */
    public function isRest($access, $request = null)
    {
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
                case 'application/html':
                    $headers["Content-Type"] = $accept;
                    $serializer = $this->get('serializer');
                    // Reason for this is the extremely simple template for
                    // showing whatever as HTML. Just send it as an array and
                    // it can be dumped
                    // more easily.
                    $data_arr = json_decode($serializer->serialize($data, 'json'), true);
                    if (isset($templates['html'])) {
                        // Here we'll let the programmer choose.
                        return $this->render($templates['html'],
                            array(
                                'data_array' => $data_arr,
                                // That name is so wrong, but can I remove it?
                                'data_entity' => $data,
                                'data' => $data,
                                ));
                    } else {
                        // And a fall back.
                        return $this->render('BisonLabCommonBundle:Default:show.html.twig', 
                            array('data' => $data_arr));
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

    /* This is more or less a hack. It does the job, but should probably be
     * more integrated into the pagedIndexAction and pagedListByEntityAction so
     * we can get the total and filtered count correct. 
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
        $serializer = $this->get('serializer');
        $content = $serializer->serialize($content_arr, 'json');
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

        $serializer = $this->get('serializer');
        $content =  $serializer->serialize($data, 'json');
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
        $serializer = $this->get('serializer');
        $headers["Content-Type"] = "application/xml";
        $content .= $serializer->serialize($data, 'xml');
        return new Response($content, $status_code, $headers);
    }

    public function returnAsYaml($request, $data, $status_code = 200) 
    {
        $serializer = $this->get('serializer');
        $headers["Content-Type"] = "text/yaml";
        $content .= $serializer->serialize($data, 'yml');
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
            'data' => $data
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
        return $this->returnRestData($request, $jsend, array(), $code);
    }

    /* Problem here? No request object I can use to find the Accept headers and
     * so on. This function should *not* be used. Use returnError or returnFail
     * instead. */
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
     * This hacks forms into accept Json.. Kinda.
     * It also tackels CSRF-protection when doing rest stuff.
     */
    public function handleForm(&$form, &$request, $access = null)
    {
        if ($data = json_decode($request->getContent(), true)) {
            foreach($data as $key => $value) {
error_log("K:" . $key);
                $request->request->set($key, $value);
            }
        }
        // Both ajax and rest - calls have to be CSRF hacked unfortunately.
        // TODO: Just get rid of this somehow. It's ugly and has to be wrong.
        if (!isset($data['_token']) && $this->isRest($access)) {
            $tm = $this->container->get('security.csrf.token_manager');
            // This is kinda bad (but it's all a hack anyway) since I
            // should rather get the CsrfFieldName (defaultFieldName in the
            // FormType. Yes, odd name).
            $token = $tm->getToken($form->getName());
            // Odd it is, but seems to be the suggested way if you Google it.
            // (Neither add nor set work deep.
            $form_data = $request->request->get($form->getName());
            $form_data['_token'] = $token;
            $form_data = $request->request->set($form->getName(), $form_data);
        }

        return $form->handleRequest($request);
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

    /* 
     * Common controller actions
     */
    public function showLogPage($request, $access, $entity_name, $id)
    {
        $em = $this->getDoctrine()->getManagerForClass($entity_name);

        $entity = $em->getRepository($entity_name)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find ' . $entity_name . ' entity.');
        }

        $log_repo = $em->getRepository('Gedmo\Loggable\Entity\LogEntry');

        $logs = $log_repo->findBy(array('objectClass' => get_class($entity),
            'objectId' => $entity->getId()));

        if ($access == 'rest') {
            return $this->returnRestData($request, $logs);
        }

        return $this->render('BisonLabCommonBundle::showLog.html.twig', 
            array(
                'entity'      => $entity,
                'logs'    => $logs,
            )
        );
    }

    /*
     * Generic paged list actions.
     */

    /* TODO: I have to add the template to this humogous list, so we can render
     * it here and always return whatever comes from this one in the
     * controllers.  Right now the controller using this one has to check on
     * response or array to know what to do. That's not a good thing. */
    public function pagedListByEntityAction($request, $access, $em, $repo, $field_name,
$entity, $entity_obj, $route, $total_amount_items, $entity_identifier_name =
null)
    {
        // Pagination with rest? 
        if ($this->isRest($access)) {
            $entities = $repo->findBy(
                array($field_name => $entity_obj));
            return $this->returnRestData($request, $entities);
        }

        $order_by  = $this->getOrderBy($request);
        $filter_by = $this->getFilterBy($request);

        $criteria = array($field_name => $entity_obj);
        if ($filter_by) {
            $criteria = array_merge($criteria, $filter_by);
        }

        if ( "all" === $request->get('page') ) {
            $entities = $repo->findBy(
                $criteria, $order_by, null, null);
            $page = 'all';
        } else {
            $page     = (int)$request->get('page') 
                      ? (int)$request->get('page') : 1;
            $offset = ($page - 1) * $this->per_page;

            $entities = $repo->findBy(
                $criteria, $order_by, $this->per_page, $offset);
        }

        $pages = ceil($total_amount_items / $this->per_page);

        $entity_identifier_name = isset($entity_identifier_name) ?  
                $entity_identifier_name : strtolower($entity);

        $routes = array();
        $router = $this->get('router');

        $routes[] = array('num' => 'All', 
                    'route' => $router->generate($route, 
                     array('page' => 'all', 
                       $entity_identifier_name => $entity_obj->getId())));

        for ($i = 1 ; $i <= $pages ; $i++) {
            $routes[] = array('num' => $i, 
                    'route' => $router->generate($route, 
                     array('page' => $i, 
                       $entity_identifier_name => $entity_obj->getId())));
        }

        $filters = $this->createFilterByForm($request, $repo);
        return array(
                'entities'     => $entities,
                'pages'        => $pages,
                'pagenum'      => $page,
                'routes'       => $routes,
                'filters'      => $filters,
                'orderby'      => $order_by,
                'total_items'  => $total_amount_items
                );
    }

    /* TODO: Same as above. Render a template here. */
    public function pagedIndexAction($request, $access, $em, $repo, $route)
    {
        if ($this->isRest($access, $request)) {
            return $this->ajaxedIndexAction($request, $access, $em, $repo);
        }

        $order_by = $this->getOrderBy($request);
        $filter_by = $this->getFilterBy($request);

        $criteria = array();
        if ($filter_by) {
            $criteria = array_merge($criteria, $filter_by);
        }
        if ( "all" === $request->get('page') ) {
            $entities = $repo->findBy($criteria, $order_by, null, null);
            $page = 'all';
        } else {
            $page     = (int)$request->get('page') 
                      ? (int)$request->get('page') : 1;
            $offset = ($page - 1) * $this->per_page;
            $entities = $repo->findBy($criteria, $order_by, $this->per_page, $offset);
        }

        // I am sure someone will, one day, pick me on the shoulder and tell
        // me Doctrine has a function for this..
        if (method_exists($repo, "countAll")) {
            $total_amount_entities = $repo->countAll($criteria);
        } else {
            // It is so stupid I want to scream.
            $total_entities = $repo->findAll();
            $total_amount_entities = count($total_entities);
        }
        $pages = ceil($total_amount_entities / $this->per_page);

        $routes = $this->createPageRoutes($request, $pages, $route, null, null);
        $filters = $this->createFilterByForm($request, $repo);

        return array(
            'entities' => $entities,
            'pages'          => $pages,
            'filters'        => $filters,
            'pagenum'        => $page,
            'routes'         => $routes,
            'total_entities' => $total_amount_entities,
        );
    }

    public function ajaxedIndexAction($request, $access, $em, $repo)
    {
        $criterias = $this->getDataTablesCriterias($request);
        if (empty($criterias)) {
            return $this->returnRestData($request, $repo->findAll());
        }

        if ($criterias['per_page'] && $criterias['per_page'] != -1) {
            $entities = $repo->findBy(
                $criterias['search'], $criterias['order_by'], $criterias['per_page'], $criterias['offset']);
            $total_amount_entities = $repo->countAll();
            $records_filtered = $repo->countAll($criterias['search']);
        } else {
            $entities = $repo->findAll();
            $total_amount_entities = $records_filtered = count($entities);
        }

        return $this->returnAsDataTablesJson($request, $entities, $records_filtered, $total_amount_entities);
    }

    /*
     * Generic helpers. (And I don't even like Unclean Bobs "Clean code")
     */
    public function createPageRoutes($request, $pages, $base_route, $object_name, $object_id)
    {
        $routes = array();
        $router = $this->get('router');

        $options['page'] = 'all';
        if ($order_by = $this->getOrderBy($request)) {
            $options['order_by'] = current(array_keys($order_by));
        }

        $routes[] = array('num' => 'All', 
                    'route' => $router->generate($base_route, $options));

        for ($i = 1 ; $i <= $pages ; $i++) {
            if ($object_name && $object_id) { 
                $options = array('page' => $i, $object_name => $object_id);
            } else {
                $options = array('page' => $i);
            }
            if ($order_by) {
                // Ok, scream and let me know a better way if there are one.
                // (The current thingie)
                $options['order_by'] = current(array_keys($order_by));
            }

            $routes[] = array('num' => $i, 
                'route' => $router->generate($base_route, $options));
        }
        return $routes;
    }

    public function createOrderByRoutes($request, $fields = array())
    {
        // I am not sure how future-proof PathInfo is here but we will find out.
        $path = $request->getPathInfo();
        $qs = $request->getQueryString();
        $routes = array();
        foreach ($fields as $field) {
            // This should be more elegant, I guess.
            $o_qs = preg_replace("/order_by=\w+/", "order_by=" . $field, $qs);

            if (!preg_match("/order_by=\w+/", $o_qs)) {
                $o_qs = empty($qs) ? "order_by=".$field : $qs . "&order_by=".$field;
            }
            $routes[] = array('route' => $path . "?" . $o_qs, 
                    'label' => ucfirst($field) , 'orderby' => $field);
        }
        return $routes;
    }

    public function createFilterByForm($request, $repo)
    {
        if (!method_exists($repo, "getFilterableProperties")) { return null; }

        // I am not sure how future-proof PathInfo is here but we will find out.
        $path = $request->getPathInfo();
        $qs = $request->getQueryString();
        $filters = array();

        $builder = $this->get('form.factory')->createNamedBuilder('filters', FormType::class);

        $i = 1;

        foreach ($repo->getFilterableProperties() as $prop => $values) {
            $choices = array();
            foreach ($values as $key => $value) {
                $key = $prop . "," . $key;
                $choices[$value] = $key;
            }
            $name = "filter_by_" . $i;
            $builder->add($name, ChoiceType::class, array(
                'choices'  => $choices,
                'label'    => "Add filter",
                'required' => false,
                'placeholder' => ucfirst($prop)
                ));
            $i++;
        }
        return $builder->getForm()->createView();
    }

    public function getDataTablesCriterias($request) 
    {
        $criterias = array();
        // Got something to do? 
        if (!$request->get('draw')) return $criterias;

        // Can just as well use the old variables.
        $columns = $request->get('columns');

        // Guess the TODO:
        if ($request->get('search')) {
            foreach ($columns as $c) {
            }
            $criterias['search'] = array();
        } else {
            $criterias['search'] = array();
        }
        $criterias['per_page'] = $request->get('length');
        $criterias['offset'] = $request->get('start');

        if ($request->get('order')) {
            // Lazy for now, just use the first order.
            $o = $request->get('order')[0];
            $criterias['order_by'] = array($columns[$o['column']]['data'] => $o['dir']);
        }
        return $criterias;
    }

    /*
     * Generic helpers. (And I don't even like Unclean Bobs "Clean code")
     */
    public function getOrderBy($request) 
    {
        // Should check against what is allowed to order by. Searchable?
        if ($order_by = $request->get('order_by')) {
            $oarr = explode(",", $order_by);
            $direction = isset($oarr[1]) ? $oarr[1] : 'ASC';
            $order_by = array($oarr[0] => $direction);
        } else {
            $order_by = null;
        }
        return $order_by;
    }

    /* 
     * Sorry folks, this looks odd and stupid. And maybe it is.
     * It has to handle both POSt and GEt and two ways of defining a filter,
     * list and string.
     */
    public function getFilterBy($request) 
    {
        $filter_by = array();
        // Should check against what is allowed to order by. Searchable?
        if ($filter = $request->get('filter_by')) {
            if (is_array($filter))
                $filter_by = array_merge($filter_by, $filter);
            else 
                $filter_by = array_merge($filter_by, array($filter));
        }

        // No, I could not use filters in an && 
        $filters_list = array();
        if ($filters = $request->get('filters'))
            if (is_array($filters))
                $filters_list = $filters;

        
        if ($filters = $request->request->get('filters'))
            if (is_array($filters))
                $filters_list = array_merge($filters, $filters_list);

        if (count($filters_list) > 0) {
            foreach($filters_list as $key => $val) {
                if (preg_match("/^filter_by/", $key) && strlen($val) > 2) {
                    $filter_by[] = $val;
                }
            } 
        }

        if (count($filter_by) > 0) {
            $filters = array();
            if (is_array($filter_by)) {
                foreach ($filter_by as $filter) {
                    $farr = explode(",", $filter);
                    $value = isset($farr[1]) ? $farr[1] : null;
                    if (!$value) { return null; }
                    $filters[$farr[0]] = $value;
                }
            } else {
                $farr = explode(",", $filter_by);
                $value = isset($farr[1]) ? $farr[1] : null;
                if (!$value) { return null; }
                $filters[$farr[0]] = $value;
            }
            return $filters;
        } else {
            $filter_by = null;
        }
        return $filter_by;
    }

    private function _serialize($data, $format)
    {
        if (method_exists($data, 'toArray')) {
            var_dump($data->toArray());
            $data = $data->toArray();
            }
    }

    /* Masking stuff. */

    /* Or kinda. Right now I cannot just throw an exception in one case and not
     * in others. The way to do that would be to change all my controllers to
     * not throw the createNotFoundException, but return it. 
     * And I'm not prepared for that, yet at least.

     * Name is kinda misleading though. It will return an error response if
     * REST and throw the usual exception if the usual web/html stuff.
     */
    public function returnNotFound($request, $text, \Exception $previous = null)
    {
        $data = array('code' => 404, 'status' => 'Not Found', 'error_text' => $text);

        /* Since I never sent the $access to this (Should I move it from a
         * function parameter in the isRest function  to using the $request
         * object and check the path there? Might as well do I think.
         */
        if (in_array('text/html', $request->getAcceptableContentTypes())) {
            throw parent::createNotFoundException($text, $previous);
        } else {
            return $this->returnFail($request, $data, 404);
        }
    }
}
