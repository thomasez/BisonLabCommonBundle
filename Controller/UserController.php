<?php

namespace BisonLab\CommonBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;
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
     * @Route("/", name="user", methods={"GET"})
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
     * @Route("/{id}/show", name="user_show", methods={"GET"})
     */
    public function showAction($id)
    {
        $userManager = $this->container->get('fos_user.user_manager');
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
     * @Route("/new", name="user_new", methods={"GET"})
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
     * @Route("/create", name="user_create", methods={"POST"})
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
     * @Route("/{id}/edit", name="user_edit", methods={"GET"})
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
     * @Route("/{id}/update", name="user_update", methods={"POST"})
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
     * Change password on a User.
     *
     * @Route("/{id}/change_password", name="user_change_password", methods={"GET", "POST"})
     */
    public function changePasswordAction(Request $request, User $user)
    {
        $form = $this->createChangePasswordForm($user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $userManager = $this->get('fos_user.user_manager');
            $password = $form->getData()['plainpassword'];
            $user->setPlainPassword($password);
            $userManager->updateUser($user);
            return $this->redirectToRoute('user_show', array('id' => $user->getId()));
        } else {
            return $this->render('BisonLabCommonBundle:User:edit.html.twig',
                array(
                'entity' => $user,
                'edit_form' => $form->createView(),
                'delete_form' => null,
            ));
        }
    }

    /**
     * Deletes a User entity.
     *
     * @Route("/{id}/delete", name="user_delete", methods={"POST", "DELETE"})
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
     * @Route("/search", name="user_search", methods={"GET"})
     */
    public function searchUserAction(Request $request, $access)
    {
        if (!$username = $request->query->get("term"))
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
            $q = $repo->createQueryBuilder('u')
                ->where('lower(u.usernameCanonical) LIKE :username')
                ->setParameter('username', strtolower($username) . '%');
            if (property_exists($class, 'full_name')) {
                $q->orWhere('lower(u.full_name) LIKE :full_name')
                ->setParameter('full_name', '%' . strtolower($username) . '%');
            }

            if ($users = $q->getQuery()->getResult()) {
                foreach ($users as $user) {
                    // TODO: Add full name.
                    $res = array(
                        'userid' => $user->getId(),
                        'value' => $user->getUserName(),
                        'label' => $user->getUserName(),
                        'username' => $user->getUserName(),
                    );
                    // Override if full name exists.
                    if (property_exists($user, 'full_name') 
                            && $user->getFullName()) {
                        $res['label'] = $user->getFullName();
                        $res['value'] = $user->getUserName();
                    }
                    $result[] = $res;
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

    /**
     * Creates a form to edit a password.
     *
     * @param User $user The user entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createChangePasswordForm(User $user)
    {
        return $this->createFormBuilder()
            ->add('plainpassword')
            ->setAction($this->generateUrl('user_change_password', array('id' => $user->getId())))
            ->setMethod('POST')
            ->getForm()
        ;
    }
}
