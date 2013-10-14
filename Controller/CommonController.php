<?php

namespace RedpillLinpro\CommonBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class CommonController extends Controller
{

    /*
     * The Context stuff
     */

    public function contextGetAction($context_config, $access, $system, $key, $value)
    {

        $em = $this->getDoctrine()->getManager();
        $request = $this->getRequest();

        $repo = $em->getRepository($context_config['entity']);

        // Grabbing only one for now.. 
        $entities = $repo->getOneByContext($system, $key,
                      $value);

        if ($access == 'rest') {
            return $this->returnRestData($this->getRequest(), $entities);
        }

        if (!$entities) {
            return $this->render('RedpillLinproCommonBundle::error.html.twig', 
                array('message' => 'Sorry, could not find what you were looking for'));
        }

        if (count($entities) == 1) {
            return $this->render($context_config['show_template'],
               $this->showAction($access, $entities->getId()));
        } else {
            // Not that it exists, yet.
            return $this->render($context_config['list_template'],
               $this->showAction($access, $entities));
        }

    }

    public function contextPostAction($context_config, $access)
    {

        $request = $this->getRequest();
        $post_data = $request->request->get('form');

        list( $system, $object_name) = explode("__", $post_data['system__object_name']);
        $object_id = $post_data['object_id'];

        return $this->contextGetAction($context_config, $access, $system, $object_name, $object_id);

    }

    public function createContextForms($context_for, $contexts) {

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
        foreach ($conf as $system_name => $object_info) {
            foreach ($object_info as $context_data) {
                $object_name = $context_data['object_name'];
                $form_name  = "context__" . $system_name . "__" . $object_name;
                $form_label = $context_data['label'];

                if (isset($context_arr[$system_name][$object_name])) {
                    $c_object = $context_arr[$system_name][$object_name];

                    $form   = $form_factory->createNamedBuilder($form_name, 'form', $c_object)
                        ->add('id', 'hidden', array('data' => $c_object->getId()))
                        ->add('external_id', 'text', array('label' => 'External ID', 'required' => false))
                        ->add('url', 'text', array('label' => 'URL', 'required' => false))
                        ->getForm();
                } else {
                    $form   = $form_factory->createNamedBuilder($form_name, 'form')
                        ->add('external_id', 'text', array('label' => 'External ID', 'required' => false))
                        ->add('url', 'text', array('label' => 'URL', 'required' => false, 'data' => $context_data['url_base']))
                        ->getForm();

                }
                $forms[] = array('label' => $form_label,
                        'form' => $form->createView());
            }
        } 
        return $forms;

    }

    public function updateContextForms($context_for, $context_class, $context_for_object) {

        $em = $this->getDoctrine()->getManager();
        $request = $this->getRequest();

        $context_conf = $this->container->getParameter('app.contexts');
        list($bundle, $object) = explode(":", $context_for);
        $conf = $context_conf[$bundle][$object];
        $forms = array();
        foreach ($conf as $system_name => $object_info) {
            foreach ($object_info as $context_data) {
                $object_name = $context_data['object_name'];
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
                        $context->setUrl($context_arr['url']);
                        $em->persist($context);
                    }
                } elseif (!empty($context_arr['external_id'])) { 
                    $context = new $context_class;
                    $context->setSystem($system_name);
                    $context->setObjectName($object_name);
                    $context->setExternalId($context_arr['external_id']);
                    $context->setUrl($context_arr['url']);
                    $context->setContextForObject($context_for_object);
                    $em->persist($context);
                } else {
                    continue;
                }

            }
        }

    }
    
    public function createContextSearchForm($config)
    {

        $choices = array();
        foreach ($config as $system => $system_config) {
            if (count($system_config) > 1) {
                foreach ($system_config as $object_config) {
                    $choices[$system . "__" .  $object_config['object_name']] = ucfirst($system) . " - " . $object_config['object_name'];
                }
            } else {
                $choices[$system . "__" .  $system_config[0]['object_name']] = ucfirst($system);
            }
        }

        return $this->createFormBuilder()
            ->add('system__object_name', 'choice', array('choices' => $choices))
            ->add('object_id', 'text')
            ->getForm();

    }

    /*
     * The REST stuff
     */

    /*
     * Basically, "rest" is the basic authen Web services, "ajax" is the 
     * same but from a web client with session data.
     *
     * I have not found any way to use two different firewalls on the
     * same path, alas, rest and ajax is the same, but different.
     */
    public function isRest($access, $request = null)
    {
        if ('rest' == $access || 'ajax' == $access) {
            return true;
        } else {
            return false;
        }
    }

    public function returnRestData($request, $data)
    {

        if (in_array('application/xml', $request->getAcceptableContentTypes())) {
            $serializer = $this->get('serializer');
            header('Content-Type: application/xml');
            echo $serializer->serialize($data, 'xml');
            return new Response('', 200);
        } elseif (in_array('application/yml;', $request->getAcceptableContentTypes())) {
            $serializer = $this->get('serializer');
            header('Content-Type: text/yaml');
            echo $serializer->serialize($data, 'yml');
            return new Response('', 200);
        } elseif (in_array('application/html', $request->getAcceptableContentTypes())) {
            header('Content-Type: application/html');
            $serializer = $this->get('serializer');
            $data_arr = json_decode($serializer->serialize($data, 'json'), true);
            return $this->render('RedpillLinproCommonBundle:Default:show.html.twig', 
                array('data' => $data_arr));
        } elseif (in_array('text/plain', $request->getAcceptableContentTypes())) {
            if (!is_string($data)) {
                throw InvalidArgumentException("Can not return non-string content as plain text.");
            }
            header('Content-Type: text/plain');
            echo $data;
            return new Response('', 200);
        } else { // Json.
            $serializer = $this->get('serializer');
            header('Content-Type: application/json');
            echo $serializer->serialize($data, 'json');
            return new Response('', 200);
        }
    }

    public function returnAsJson($entity) 
    {
        // But why?
        echo $entity->toJson(true);
        return new Response('', 200);
    }

    public function returnEntitiesAsJson($entities) 
    {
        // json encode does not send an empty {} if nothing in it.
        if (count($entities) == 0) {
            return new Response('{}', 200);
        }

        $arr = array();
        foreach ($entities as $entity) {
            $arr[] = $entity->toArray(true);
        }
        $json = new JsonEncoder();
        echo $json->encode($arr, 'json');
        return new Response('', 200);
    }

    /* 
     * Common controller actions
     */
    public function showLogPage($access, $entity_name, $id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository($entity_name)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find ' . $entity_name . ' entity.');
        }

        $log_repo = $em->getRepository('Gedmo\Loggable\Entity\LogEntry');

        $logs = $log_repo->findBy(array('objectClass' => get_class($entity),
            'objectId' => $entity->getId()));

        if ($access == 'rest') {
            return $this->returnRestData($this->getRequest(), $logs);
        }

        return $this->render('RedpillLinproCommonBundle::showLog.html.twig', 
            array(
                'entity'      => $entity,
                'logs'    => $logs,
            )
        );
    }

    /*
     * Generic paged list actions.
     */

    public function pagedListByEntityAction($access, $em, $repo, $field_name, $entity, $entity_obj, $route, $total_amount_items, $entity_identifier_name = null)
    {

        $request = $this->getRequest();

        // Pagination with rest? 
        if ($this->isRest($access)) {
            $entities = $repo->findBy(
                array($field_name => $entity_obj));
            return $this->returnRestData($this->getRequest(), $entities);
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

// The ever needing debug, just commented out
// error_log("entities:" . count($entities) . "pages:$pages page:$page, offset:$offset pp:" . $this->per_page);

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

        return array(
                'entities'     => $entities,
                'pages'        => $pages,
                'pagenum'      => $page,
                'routes'       => $routes,
                'orderby'     => $order_by,
                'total_items'  => $total_amount_items
                );

    }

    public function pagedIndexAction($access, $em, $repo, $route)
    {

        if ($access == 'rest') {
            $entities = $repo->findAll();
            return $this->returnRestData($this->getRequest(), $entities);
        }

        $request  = $this->getRequest();
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

        if (!method_exists($repo, "getFilerableProperties")) { return null; }

        // I am not sure how future-proof PathInfo is here but we will find out.
        $path = $request->getPathInfo();
        $qs = $request->getQueryString();
        $filters = array();

        $builder = $this->get('form.factory')->createNamedBuilder('filters', 'form');

        $i = 1;

        foreach ($repo->getFilerableProperties() as $prop => $values) {
            $choices = array();
            foreach ($values as $value) {
                $key = $prop . "," . $value;
                $choices[$key] = $value;
            }
            $name = "filter_by_" . $i;
            $builder->add($name, 'choice', array(
                'choices'  => $choices,
                'label'    => "Add filter",
                'required' => false,
                'empty_value' => ucfirst($prop)
                ));
            $i++;
        }

        return $builder->getForm()->createView();
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

    public function getFilterBy($request) 
    {
        // Should check against what is allowed to order by. Searchable?
        $filter_by = $request->get('filter_by');

        if (!is_array($filter_by)) $filter_by = array();

        // This is ohh, so annoying. I just want to POSt withj []'s!
        $filter_by_post = $request->request->get('filters');
        if ($filter_by_post) {
            foreach($filter_by_post as $key => $val) {
                if (preg_match("/^filter_by/", $key) && strlen($val) > 3) {
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

}

