<?php

namespace BisonLab\CommonBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;

use Symfony\Component\Serializer\Encoder\XmlEncoder;

trait ContextTrait
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
        if ($this->isRest($request)) {
            return $this->returnRestData($request, $entities, 
                array('html' => $context_config['list_template']));
        }

        if (!$entities) {
            return $this->render('BisonLabCommonBundle::message.html.twig', 
                array('message' => 'Sorry, could not find what you were looking for'));
        }

        if (count($entities) == 1) {
            // Need to do this for BC.
            $entity = (is_array($entities) || $entities instanceof \ArrayAccess)
                ? $entities[0]
                : $entities;

            $eid = $entity->getId();

            $classMethod = new \ReflectionMethod($this,"showAction");
            $classParams = $classMethod->getParameters();
            $argumentCount = count($classParams);
            /*
             *  Symfony now also handles Entity injection if the class is stype
             *  hinted. I have to handle that while not having to be bothered
             *  with the class. Ans also be backwards compatible with sending
             *  "ID".
             * TODO: Ponder about using redirect instead.
             */
            if ($argumentCount == 3 && $classParams[2]->__toString())
                return $this->showAction($request, $access, $entity);
            elseif ($argumentCount == 3)
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
        trigger_error('The '.__METHOD__.' method is deprecated. Please contextGetAction else instead', E_USER_DEPRECATED);
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
        if (!$conf)
            return $forms;

        foreach ($conf as $system_name => $object_info) {
            foreach ($object_info as $context_object_config) {
                // If it's not supposed to be editable, do not make it
                // editable.  (But should it be addable? No. If so, create
                // "write_once" or something.
                if ($context_object_config['type'] == "readonly")
                    continue;

                $object_name = $context_object_config['object_name'];
                $form_name  = "context__" . $system_name . "__" . $object_name;
                $form_label = $context_object_config['label'];

                // TODO: Use types more active. Like EternalId should be
                // compulsary in more or less all cases except
                // "informal_url_only".
                $has_value = false;
                $required  = count($contexts) > 0 && isset($context_object_config['required']);
                if (isset($context_arr[$system_name][$object_name])) {
                    $has_value = true;
                    $c_object = $context_arr[$system_name][$object_name];

                    $form = $form_factory->createNamedBuilder($form_name,
                            FormType::class, $c_object)
                        ->add('id', HiddenType::class, array(
                          'data' => $c_object->getId()))
                        ->add('external_id', TextType::class, array(
                          'label' => 'External ID', 'required' => $required));
                } else {
                    $form = $form_factory->createNamedBuilder($form_name,
                            FormType::class)
                        ->add('external_id', TextType::class, array(
                          'label' => 'External ID', 'required' => $required));
                }

                /* Only these two methods shall make it possible to edit/add a
                 * URL in the forms. The rest will be calculated
                 * automatically.*/
                if (!isset($context_object_config['url_from_method'])) {
                    error_log("No url_from_method for " . $systen_name
                            . "::" . $object_name);
                } else {
                    if ($context_object_config['url_from_method'] == "manual" 
                      || $context_object_config['url_from_method'] == "editable") {
                        $form->add('url', TextType::class, 
                            array('label' => 'URL', 'required' => false));
                    }
                }
                $forms[] = array(
                    'label'     => $form_label,
                    'name'      => $form_name,
                    'has_value' => $has_value,
                    'required'  => isset($context_object_config['required']),
                    'form'      => $form->getForm()->createView());
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
                    $context = $em->getRepository($context_class)
                        ->find($context_arr['id']);
                    if (empty($context_arr['external_id']) 
                            && empty($context_arr['url'])) { 
                        // No need for an empty context.
                        $owner->removeContext($context);
                        $em->remove($context);
                    } else {
                        $context->setExternalId(trim($context_arr['external_id']));
                        if (empty($context_arr['url']) ) {
                            $context->setUrl(self::createContextUrl(
                                $context_arr, $context_object_config));
                        } else {
                            $context->setUrl($context_arr['url']);
                        }
                        $em->persist($context);
                    }
                } elseif (!empty($context_arr['external_id']) 
                        || !empty($context_arr['url'])) { 
                    $context = new $context_class;
                    $context->setSystem($system_name);
                    $context->setObjectName($object_name);
                    $context->setExternalId(trim($context_arr['external_id']));
                    if (empty($context_arr['url'])) {
                        $context->setUrl(self::createContextUrl($context_arr,
                            $context_object_config));
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
     * Showing the context logs.
     */
    public function showContextLogPage($request, $access, $entity_name, $id)
    {
        $em = $this->getDoctrine()->getManagerForClass($entity_name);
        $entity = $em->getRepository($entity_name)->find($id);
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find '
                . $entity_name . ' entity.');
        }

        $bcomm_em = $this->getDoctrine()->getManagerForClass("BisonLabCommonBundle:ContextLog");
        $log_repo = $bcomm_em->getRepository('BisonLab\CommonBundle\Entity\ContextLog');
        $logs = $log_repo->findBy(array(
            'owner_class' => $entity_name,
            'owner_id' => $id)
            , array('logged_at' => 'DESC'));

        if ($access == 'rest') {
            return $this->returnRestData($request, $logs);
        }

        return $this->render('BisonLabCommonBundle::showContextLog.html.twig', 
            array(
                'entity' => $entity,
                'logs'   => $logs,
            )
        );
    }
}
