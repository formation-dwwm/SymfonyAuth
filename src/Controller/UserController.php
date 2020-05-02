<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

/**
 * @Route("/user")
 */
class UserController extends AbstractController
{

    private function generateToken()
    {
        return rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
    }
    
    /**
     * @Route("/register", name="user_register", methods={"GET","POST"})
     */
    public function register(Request $request, UserPasswordEncoderInterface $encoder, \Swift_Mailer $mailer)
    {

        $user = new User();

        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid())
        {
            $recaptcha = new \ReCaptcha\ReCaptcha('6LfQcegUAAAAAEt9EhjAH08rclmL7ABnRIAKcEY9');
            $resp = $recaptcha->verify($_POST['g-recaptcha-response']);

            if ($resp->isSuccess()) 
            {
                $hash = $encoder->encodePassword($user, $user->getPassword());
                $user->setPassword($hash);
    
                $user->setConfirmationToken($this->generateToken());
                $user->setAccountActivated(false);
                $user->setRoles(array('ROLE_USER'));
                
                $manager = $this->getDoctrine()->getManager();
                $manager->persist($user);
                $manager->flush();
    
                $message = (new \Swift_Message('Symfony Auth | Confirmation de compte'))
                   ->setFrom(['vincefalcao@gmail.com' => 'Symfony Auth'])
                   ->setTo($user->getEmail())
                    ->setBody(
                        $this->renderView('emails/account_confirmation.html.twig',[
                            'username' => $user->getUsername(),
                            'id' => $user->getId(),
                            'token' => $user->getConfirmationToken()
                        ]),
                        'text/html'
                    );
    
                $mailer->send($message);
    
                $this->addFlash('success', 'Votre compte a bien été enregistré. Un mail vous a été envoyé à : ' . $user->getEmail() . '. Veuillez cliquer sur le lien de vérification afin d\'activer votre compte.');
                return $this->redirectToRoute('user_login');
            
            } else {
               $errors = $resp->getErrorCodes();
            }
        }
              
        return $this->render('user/register.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/confirm/{token}/{id}", name="confirm_account")
     */
    public function confirmAccount($token, $id)
    {
        $manager = $this->getDoctrine()->getManager();      
        $user = $manager->getRepository(User::class)->findOneBy(['id' => $id]);     
        $userToken = $user->getConfirmationToken();
        
        if($token === $userToken) 
        {
            $user->setConfirmationToken(null)
                ->setAccountActivated(true);
           
           $manager->persist($user);
           $manager->flush();
           
           $this->addFlash('success', 'Votre compte a bien été activé. Vous pouvez vous connecter.');

           return $this->redirectToRoute('user_login');
        }
    }

    /**
     * @Route("/login", name="user_login")
     */
    public function login(AuthenticationUtils $authenticationUtils)
    {
        $error = $authenticationUtils->getLastAuthenticationError();

        return $this->render('user/login.html.twig', [
            'error' => $error
        ]);
    }

    /**
     * @Route("/logout", name="user_logout")
     */
    public function logout(){}

    /**
     * @Route("/{id}", name="user_profile", methods={"GET"})
     */
    public function profile(User $user): Response
    {
        return $this->render('user/profile.html.twig', [
            'user' => $user,
        ]);
    }

/**
     * @Route("/{id}/edit", name="user_edit", methods={"GET","POST"})
     */
    public function editAccount(Request $request, User $user, UserPasswordEncoderInterface $encoder)
    {
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $hash = $encoder->encodePassword($user, $user->getPassword());
            $user->setPassword($hash);
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('user_profile');
        }

        return $this->render('user/edit.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="user_delete", methods={"DELETE"})
     */
    public function delete(Request $request, User $user): Response
    {
        if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($user);
            $entityManager->flush();
        }

        return $this->redirectToRoute('home');
    }
}
