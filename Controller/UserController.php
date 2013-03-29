<?php

namespace RedpillLinpro\CommonBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use RedpillLinpro\CommonBundle\Entity\User;
use RedpillLinpro\CommonBundle\Form\UserType;

use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * User controller.
 *
 * @Route("/user")
 */
class UserController extends Controller
{
    /**
     * Lists all User entities.
     *
     * @Route("/", name="user")
     * @Template()
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $entities = $em->getRepository('RedpillLinproCommonBundle:User')->findAll();

        return array(
            'entities' => $entities,
        );
    }

    /**
     * Finds and displays a User entity.
     *
     * @Route("/{id}/show", name="user_show")
     * @Template()
     */
    public function showAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('RedpillLinproCommonBundle:User')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find User entity.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return array(
            'entity'      => $entity,
            'delete_form' => $deleteForm->createView(),
        );
    }

    /**
     * Displays a form to create a new User entity.
     *
     * @Route("/new", name="user_new")
     * @Template()
     */
    public function newAction()
    {
        $entity = new User();
        $form   = $this->createForm(new UserType(), $entity);

        return array(
            'entity' => $entity,
            'form'   => $form->createView(),
        );
    }

    /**
     * Creates a new User entity.
     *
     * @Route("/create", name="user_create")
     * @Method("POST")
     * @Template("RedpillLinproCommonBundle:User:new.html.twig")
     */
    public function createAction(Request $request)
    {
        $entity  = new User();
        $form = $this->createForm(new UserType());
        $form->bind($request);

        $post_data = $request->request->get('user');

        if ($form->isValid()) {

            $userManager = $this->container->get('fos_user.user_manager');
            $user = $userManager->createUser();
            $user->setUsername($post_data['username']);
            $user->setEmail($post_data['email']);
            $user->setPlainPassword($post_data['password']);
            $user->setRoles(array_values($post_data['roles']));

            $userManager->updateUser($user);

            return $this->redirect($this->generateUrl('user'));
        }

        return array(
            'entity' => $entity,
            'form'   => $form->createView(),
        );
    }

    /**
     * Displays a form to edit an existing User entity.
     *
     * @Route("/{id}/edit", name="user_edit")
     * @Template()
     */
    public function editAction($id)
    {
        $em = $this->getDoctrine()->getManager();
        $entity = $em->getRepository('RedpillLinproCommonBundle:User')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find User entity.');
        }

        $editForm = $this->createForm(new UserType(), $entity);
        $deleteForm = $this->createDeleteForm($id);

        return array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        );
    }

    /**
     * Edits an existing User entity.
     *
     * @Route("/{id}/update", name="user_update")
     * @Method("POST")
     * @Template("RedpillLinproCommonBundle:User:edit.html.twig")
     */
    public function updateAction(Request $request, $id)
    {
        $userManager = $this->container->get('fos_user.user_manager');

        $post_data = $request->request->get('user');

        $user = $userManager->findUserBy(array('id' => $id));

        if (!$user) {
            throw $this->createNotFoundException('Unable to find User.');
        }

        $em = $this->getDoctrine()->getManager();
        $entity = $em->getRepository('RedpillLinproCommonBundle:User')->find($id);

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createForm(new UserType());
        $editForm->bind($request);

        if ($editForm->isValid()) {
            
            $user->setEmail($post_data['email']);
            $user->setUsername($post_data['username']);

            if (isset($post_data['password']))
                $user->setPlainPassword($post_data['password']);

            if (isset($post_data['locked'])) {
                $user->setLocked(true);
            } else {
                $user->setLocked(false);
            }
            if (isset($post_data['enabled'])) {
                $user->setEnabled(true);
            } else {
                $user->setEnabled(false);
            }

            $user->setRoles(array_values($post_data['roles']));

            $userManager->updateUser($user);

            return $this->redirect($this->generateUrl('user'));

        }

        return array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        );
    }

    /**
     * Deletes a User entity.
     *
     * @Route("/{id}/delete", name="user_delete")
     * @Method("POST")
     */
    public function deleteAction(Request $request, $id)
    {
        $form = $this->createDeleteForm($id);
        $form->bind($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('RedpillLinproCommonBundle:User')->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Unable to find User entity.');
            }

            $em->remove($entity);
            $em->flush();
        }

        return $this->redirect($this->generateUrl('user'));
    }

    private function createDeleteForm($id)
    {
        return $this->createFormBuilder(array('id' => $id))
            ->add('id', 'hidden')
            ->getForm()
        ;
    }
}
