<?php

namespace BisonLab\CommonBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use JMS\Serializer\SerializationContext;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;

use Symfony\Component\Serializer\Encoder\XmlEncoder;

class CommonController extends AbstractController
{
    use ContextTrait;
    use RestTrait;

    /* 
     * Common controller actions
     */

    /* 
     * Generic update of attributes on an entity
     * I could drop the "attributes" and make it all flat, but this give me and
     * the poster/patcher more control.
     *
     * Really simple now, but may end up being worth being here.
     */
    public function updateAttributes(Request $request, &$entity)
    {
        if ($data = json_decode($request->getContent(), true)) {
            if (isset($data['attributes']))
                $attributes = $data['attributes'];
            else
                return false;
        } else {
            $attributes = $request->request->get('attributes');
        }
        foreach ($attributes as $a => $v) {
            $entity->setAttribute($a, $v);
        }
    }

    /* 
     * Showing the log from gedmo Loggable.
     */
    public function showLogPage($request, $access, $entity_name, $id, $options = array())
    {
        $em = $this->getDoctrine()->getManagerForClass($entity_name);
        $entity = $em->getRepository($entity_name)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find ' . $entity_name . ' entity.');
        }

        $log_repo = $em->getRepository('Gedmo\Loggable\Entity\LogEntry');

        $logs = $log_repo->findBy(array(
            'objectClass' => get_class($entity),
            'objectId'    => $entity->getId())
            , array('loggedAt' => 'DESC'));

        if ($this->isRest($access)) {
            return $this->returnRestData($request, $logs, $options);
        }

        return $this->render('@BisonLabCommon/showLog.html.twig', 
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
    public function pagedListByEntityAction($request, $access, $em, $repo,
            $field_name, $entity, $entity_obj, $route, $total_amount_items,
            $entity_identifier_name = null)
    {

        // Pagination with rest? 
        if ($this->isRest($access)) {
            return $this->ajaxedIndexAction($request, $access, $em, $repo,
                $field_name, $entity_obj);
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

    public function ajaxedIndexAction($request, $access, $em, $repo,
        $field_name = null, $entity_obj = null)
    {
        $order_by = $this->getOrderBy($request);
        $filter_by = $this->getFilterBy($request);

        if (!$request->get('draw')) {
            if ($field_name && $entity_obj) {
                $entities = $repo->findBy(
                    array($field_name => $entity_obj));
                return $this->returnRestData($request, $entities);
            } else {
                return $this->returnRestData($request, $repo->findAll());
            }
        }

        $qb = $repo->createQueryBuilder('s');
        if ($field_name && $entity_obj) {
            $qb->andWhere('s.'.$field_name .' = :entity_obj');
            $qb->setParameter('entity_obj', $entity_obj);
        }

        if ($filter_by) {
            foreach ($filter_by as $key => $value) {
                $qb->andWhere('s.'.$key .' = :value');
                $qb->setParameter('value', $value);
            }
        }
        
        // Cheating, and should rather hack the DataTables\Builder instead.
        // But later.
        $columns = $request->get('columns');
        $aliases = array();
        foreach ($columns as $c) {
            $aliases[$c['data']] = 's.' . $c['data'];
        }

        /*
         * Gonna build the request params here. Not liking giving out _GET to
         * the bundle. Better run it through some sanitizing in the symfony
         * component handling Request object. (At least I hope there is some
         * of it there) 
         *
         * * columns
         * * order
         * * start
         * * length
         * * search
         * * draw
         */
        $request_params = array();
        if ($d = $request->get('columns'))
            $request_params['columns'] = $request->get('columns');
        if ($d = $request->get('order'))
            $request_params['order'] = $request->get('order');
        if ($d = $request->get('start'))
            $request_params['start'] = $request->get('start');
        if ($d = $request->get('length'))
            $request_params['length'] = $request->get('length');
        if ($d = $request->get('draw'))
            $request_params['draw'] = $request->get('draw');

        /*
         * Doing my own search. Should consider hacking this into the
         * datatablesbundle since the problem with searching on numbers will be
         * around.
         */
        if ($search_params = $request->get('search')) {
            // $request_params['search'] = $request->get('search');
            $cn = $repo->getClassName();
            $em = $qb->getEntityManager();
            $md = $em->getClassMetadata($cn);

            $columnField = "data";
            $columns = &$request_params['columns'];
            $c = count($columns);
            $string_search = false;
            $integer_search = false;
            if ($value = trim($search_params['value'])) {
                $orX = $qb->expr()->orX();
                for ($i = 0; $i < $c; $i++) {
                    $column = &$columns[$i];
                    if ($column['searchable'] == 'true') {
                        $type = $md->fieldMappings[$column[$columnField]]['type'];
                        if (array_key_exists($column[$columnField], $aliases)) {
                            $column[$columnField] = $aliases[$column[$columnField]];
                        }
                        // TODO: Any other integer like types?
                        if ($type == "integer") {
                            if (is_numeric($value)) {
                                $orX->add($qb->expr()->eq($column[$columnField], ':int_search'));
                                $integer_search = true;
                            }
                        // TODO: Any other types not able to handle liower or
                        // like??
                        } else {
                            $searchColumn = "lower(" . $column[$columnField] . ")";
                            $orX->add($qb->expr()->like($searchColumn, 'lower(:search)'));
                            $string_search = true;
                        }
                    }
                }
                if ($integer_search)
                    $qb->andWhere($orX)->setParameter('int_search', $value);
                if ($string_search)
                    $qb->andWhere($orX)->setParameter('search', "%{$value}%");
            }
        }
        $datatables = (new \Doctrine\DataTables\Builder())
            ->withColumnAliases($aliases)
            ->withIndexColumn('s.id')
            ->withQueryBuilder($qb)
            ->withReturnCollection(true)
            ->withCaseInsensitive(true)
            ->withRequestParams($request_params);

        $result = $datatables->getResponse();
        return $this->returnAsDataTablesJson($request,
            $result['data'],
            $result['recordsFiltered'],
            $result['recordsTotal']
            );
    }

    /*
     * Generic helpers. (And I don't even like Unclean Bobs "Clean code")
     */
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

        $criterias['search'] = array();
        $criterias['per_page'] = $request->get('length');
        $criterias['offset'] = $request->get('start');

        // Can just as well use the old variables.
        $columns = $request->get('columns');

        // Guess the TODO:
        // For now I will take for granted there is only one value to search
        // for. And that there is no regexp, just a value.
        if ($search = $request->get('search')) {
            if ($value = trim($search['value'])) {
                $criterias['search'] = array();
                foreach ($columns as $c) {
                    if (isset($c['searchable']) 
                        && $c['searchable'] == "true") {
                        $key = empty($c['name']) ? $c['data'] : $c['name'];
                        if (!$key) continue;
                        $criterias['search'][$key] = $value;
                    }
                }
            }
        }

        if ($request->get('order')) {
            // Lazy for now, just use the first order.
            $o = $request->get('order')[0];
            $column_num = $o['column'];
            if ($col = $columns[$column_num]['data']) {
                $criterias['order_by'] = array($col => $o['dir']);
            } else {
                $criterias['order_by'] = null;
            }
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
// Only trigger if this is actually being used.
trigger_error('The '.__METHOD__.' method is deprecated. Please use something else instead', E_USER_DEPRECATED);
            $order_by = null;
        }
        return $order_by;
    }

    /* 
     * Sorry folks, this looks odd and stupid. And maybe it is.
     * It has to handle both POSt and GEt and two ways of defining a filter,
     * list and string.
     * Yes, it should and could be simplified.
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
// Only trigger if this is actually being used.
trigger_error('The '.__METHOD__.' method is deprecated. Please use something else instead', E_USER_DEPRECATED);
            $filters = array();
            if (is_array($filter_by)) {
                foreach ($filter_by as $idx => $filter) {
                    if (is_numeric($idx)) {
                        $farr = explode(",", $filter);
                        $value = isset($farr[1]) ? $farr[1] : null;
                        if (!$value) { return null; }
                        $filters[$farr[0]] = $value;
                    } else {
                        $filters[$idx] = $filter;
                    }
                }
            } else {
                $farr = explode(",", $filter_by);
                $value = isset($farr[1]) ? $farr[1] : null;
                if (!$value) { return null; }
                $filters[$farr[0]] = $value;
            }
            return $filters;
        } elseif (count($filters_list) > 0)  {
            return $filters_list;
        } else {
            $filter_by = null;
        }
        return $filter_by;
    }

    private function _serialize($data, $format)
    {
        if (method_exists($data, '__toArray')) {
            $serialized = $data->__toArray();
        } else {
            $serializer = $this->get('jms_serializer');
            $serialized = $serializer->serialize($data, $format, SerializationContext::create()->enableMaxDepthChecks());
        }
        return $serialized;
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
trigger_error('The '.__METHOD__.' method is deprecated. Please use the not found exception again.', E_USER_DEPRECATED);

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
