<?php

namespace BisonLab\CommonBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;

use BisonLab\CommonBundle\Entity\User;
use BisonLab\CommonBundle\Form\UserType;
use BisonLab\CommonBundle\Controller\CommonController as CommonController;

use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * User Admin controller.
 * The stuff users can change themselves is hopefully handled by the
 * FOS User Bundle.
 *
 * @Route("/{access}/useradmin", defaults={"access" = "web"}, requirements={"web|rest|ajax"})
 */
class UserController extends CommonController
{
    /**
     * Lists all User entities.
     *
     * @Route("/", name="user")
     */
    public function indexAction()
    {
        $userManager = $this->container->get('fos_user.user_manager');

        $params = array(
            'entities' => $userManager->findUsers()
        );
        return $this->render('BisonLabCommonBundle:User:index.html.twig',
            $params);
    }

    /**
     * Finds and displays a User entity.
     *
     * @Route("/{id}/show", name="user_show")
     */
    public function showAction($id)
    {
        $user = $userManager->findUserBy(array('id' => $id));

        if (!$user) {
            throw $this->createNotFoundException('Unable to find User');
        }

        $deleteForm = $this->createDeleteForm($id);

        $params = array(
            'entity'      => $user,
            'delete_form' => $deleteForm->createView(),
        );
        return $this->render('BisonLabCommonBundle:User:show.html.twig',
            $params);
    }

    /**
     * Displays a form to create a new User entity.
     *
     * @Route("/new", name="user_new")
     */
    public function newAction()
    {
        $user = new User();
        $form = $this->createForm(UserType::class, $user,
            array('action' =>
                $this->generateUrl('user_create')));
        $form->add('plain_password', PasswordType::class, array('label' => 'Password', 'required' => true));

        $params = array(
            'entity' => $user,
            'form'   => $form->createView(),
        );
        return $this->render('BisonLabCommonBundle:User:new.html.twig',
            $params);
    }

    /**
     * Creates a new User entity.
     *
     * @Route("/create", name="user_create")
     * @Method("POST")
     */
    public function createAction(Request $request)
    {
        $userManager = $this->container->get('fos_user.user_manager');
        $user = $userManager->createUser();
        $form = $this->createForm(UserType::class, $user,
            array('action' =>
                $this->generateUrl('user_create')));
        $form->add('plain_password', PasswordType::class, array('label' => 'Password', 'required' => true));

        $form->handleRequest($request);

        if ($form->isValid()) {
            $userManager->updateUser($user);
            return $this->redirect($this->generateUrl('user'));
        }

        $params = array(
            'entity' => $user,
            'form'   => $form->createView(),
        );
        return $this->render('BisonLabCommonBundle:User:new.html.twig',
            $params);
    }

    /**
     * Displays a form to edit an existing User entity.
     *
     * @Route("/{id}/edit", name="user_edit")
     */
    public function editAction($id)
    {
        $userManager = $this->container->get('fos_user.user_manager');
        $user = $userManager->findUserBy(array('id' => $id));

        if (!$user) {
            throw $this->createNotFoundException('Unable to find User.');
        }

        $editForm = $this->createForm(UserType::class, $user,
            array('action' =>
                $this->generateUrl('user_update', array('id' => $id))));
        $deleteForm = $this->createDeleteForm($id);

        $params = array(
            'entity'      => $user,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        );
        return $this->render('BisonLabCommonBundle:User:edit.html.twig',
            $params);
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
        $user = $userManager->findUserBy(array('id' => $id));

        if (!$user) {
            throw $this->createNotFoundException('Unable to find User');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createForm(UserType::class, $user,
            array('action' =>
                $this->generateUrl('user_update', array('id' => $id))));
        $editForm->handleRequest($request);

        if ($editForm->isValid()) {
            $userManager->updateUser($user);
            return $this->redirect($this->generateUrl('user'));
        }

        $params = array(
            'entity'      => $user,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        );
        return $this->render('BisonLabCommonBundle:User:edit.html.twig',
            $params);
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
            $userManager = $this->container->get('fos_user.user_manager');
            $user = $userManager->findUserBy(array('id' => $id));

            if (!$user) {
                throw $this->createNotFoundException('Unable to find User');
            }
            $userManager->deleteUser($user);
        }
        return $this->redirect($this->generateUrl('user'));
    }

    /**
     * @Route("/search", name="user_search")
     * @Method("GET")
     */
    public function searchUserAction(Request $request, $access)
    {
        if ($this->isRest($access))
            $username = $request->query->get("term");
        else 
            $username = $request->query->get("username");

        // Gotta be able to handle two-letter usernames.
        if (strlen($username) > 1) {
            $userManager = $this->container->get('fos_user.user_manager');
            /* No searching for users in the manager. */
            // $users = $userManager->findUserByUsername($username);
            $class = $userManager->getClass();
            $em = $this->getDoctrine()->getManagerForClass($class);
            $repo = $em->getRepository($class);
            $result = array();
            if ($users = $repo->createQueryBuilder('u')
                ->where('lower(u.usernameCanonical) LIKE :username')
                ->setParameter('username', strtolower($username) . '%')
                ->getQuery()->getResult()) {
                    foreach ($users as $user) {
                        // TODO: Add full name.
                        $result[] = array(
                            'userid' => $user->getId(),
                            'username' => $user->getUserName(),
                            'label' => $user->getUserName(),
                            'value' => $user->getUserName(),
                        );
                    }        
            }
        } else {
            $result = "Too little information provided for a viable search";
        }

        if ($this->isRest($access)) {
            // Format for autocomplete.
            return $this->returnRestData($request, $result);
        }

        $params = array(
            'entities'      => $users,
        );
        return $this->render('BisonLabCommonBundle:User:index.html.twig',
            $params);
    }

    private function createDeleteForm($id)
    {
        return $this->createFormBuilder(array('id' => $id))
            ->add('id', HiddenType::class)
            ->getForm()
        ;
    }
}
