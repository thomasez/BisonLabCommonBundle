<?php

namespace BisonLab\CommonBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use BisonLab\CommonBundle\Entity\User;
use BisonLab\CommonBundle\Form\UserType;

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
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $entities = $em->getRepository('BisonLabCommonBundle:User')->findAll();

        $params = array(
            'entities' => $entities,
        );
        return $this->render('BisonLabCommonBundle:User:index.html.twig', $params);

    }

    /**
     * Finds and displays a User entity.
     *
     * @Route("/{id}/show", name="user_show")
     */
    public function showAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('BisonLabCommonBundle:User')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find User entity.');
        }

        $deleteForm = $this->createDeleteForm($id);

        $params = array(
            'entity'      => $entity,
            'delete_form' => $deleteForm->createView(),
        );
        return $this->render('BisonLabCommonBundle:User:show.html.twig', $params);
    }

    /**
     * Displays a form to create a new User entity.
     *
     * @Route("/new", name="user_new")
     */
    public function newAction()
    {
        $entity = new User();
        $form   = $this->createForm(UserType::class, $entity);

        $params = array(
            'entity' => $entity,
            'form'   => $form->createView(),
        );
        return $this->render('BisonLabCommonBundle:User:new.html.twig', $params);
    }

    /**
     * Creates a new User entity.
     *
     * @Route("/create", name="user_create")
     * @Method("POST")
     */
    public function createAction(Request $request)
    {
        $entity  = new User();
        $form = $this->createForm(UserType::class);
        $form->handleRequest($request);

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

        $params = array(
            'entity' => $entity,
            'form'   => $form->createView(),
        );
        return $this->render('BisonLabCommonBundle:User:new.html.twig', $params);
    }

    /**
     * Displays a form to edit an existing User entity.
     *
     * @Route("/{id}/edit", name="user_edit")
     */
    public function editAction($id)
    {
        $em = $this->getDoctrine()->getManager();
        $entity = $em->getRepository('BisonLabCommonBundle:User')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find User entity.');
        }

        $editForm = $this->createForm(UserType::class, $entity);
        $deleteForm = $this->createDeleteForm($id);

        $params = array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        );
        return $this->render('BisonLabCommonBundle:User:edit.html.twig', $params);
    }

    /**
     * Edits an existing User entity.
     *
     * @Route("/{id}/update", name="user_update")
     * @Method("POST")
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
        $entity = $em->getRepository('BisonLabCommonBundle:User')->find($id);

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createForm(UserType::class);
        $editForm->handleRequest($request);

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

        $params = array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        );
        return $this->render('BisonLabCommonBundle:User:edit.html.twig', $params);
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
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('BisonLabCommonBundle:User')->find($id);

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
            ->add('id', 'Symfony\Component\Form\Extension\Core\Type\HiddenType')
            ->getForm()
        ;
    }
}
