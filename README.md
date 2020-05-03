# SymfonyAuth

## Installation

1) >git clone https://github.com/formation-dwwm/SymfonyAuth.git
2) >cd SymfonyAuth/
3) >composer install && npm install
4) >npm run dev-server
5) >php bin/console server:run  ou  symfony serve

## Configuration

1) Editer le fichier .env en rentrant ses identifiants mySQL puis créer la BDD:
    >php bin/console d:d:c  
    
    et faire les migrations:
    >php bin/console doctrine:make:migrations
    
    
2) Pour utiliser le SMTP Gmail: toujours dans .env modifier la ligne suivante:  
    >MAILER_URL=gmail://adresseEmail:motDePasse@localhost  
    
    en renseignant son adresse email et son mot de passe puis autoriser les applications moins sécurisées sur sa boite mail:
    https://myaccount.google.com/lesssecureapps.
    
# "Tuto"
    
## Mise en place de la vérification

1) ajouter les champs : $confirmation_token et $account_activated à son entité  

2) Installer SwiftLMailer:  composer require symfony/swiftmailer-bundle  
    ( Tuto SwiftMailer: https://codereviewvideos.com/course/symfony-4-beginners-tutorial/video/send-email-symfony-4, 
        doc Symfony: https://symfony.com/doc/current/email.html?utm_source=recordnotfound.com )
3) Dans la fonction de création de compte:
   - Si le formulaire est envoyé : générer et stocker un token et passer account_activated à 0
   - Utiliser swift_mailer pour envoyer l'email qui se base sur un template twig qui contient un lien qui mène a la route :                  confirm/{token}/{id}
   - Dans le UserController implémenter une fonction confirmAccount(): trouver l'utilisateur grace a l'id dans la route et recuperer son        token en base de données,
        puis verifier si celui-ci est egale au token présent dans la route. Si oui setActivatedAccount == 1 et token == null et                 rediriger vers le login. Sinon rediriger vers un template twig indiquant une erreur.
8) Implémenter une fonction isEnabled() dans l'entité User qui retourne true si accountActivated = 1 sinon retourne false.
9) Dans ./src créer un dossier Security et créer UserChecker.php. 
10) Implémenter la class UserChecker implements UserCheckerInterface
11) Implémenter les méthodes de l'interface et rajouter dedans la méthode isEnabled() qu'on a créer dans l'entity User.
12) Dans le main de security.yaml rajouter:  
    pattern: ^/  
    user_checker: App\Security\UserChecker

## Google reCaptcha v3

rendez-vous sur https://www.google.com/recaptcha/intro/v3.html  
suivre les indications de google..(renseignez bien votre adresse locale 127.0.0.1 pour le domaine)  
ajouter dans le formulaire register la balise et les 2 scripts google en mettant votre clé public aux endroits indiqués:
```
{{ form_start(form) }}
    {{ form_widget(form) }}
    <input type="hidden" id="g-recaptcha-response" name="g-recaptcha-response">
    <button class="btn btn-success">Save</button>
{{ form_end(form) }}


{# CAPTCHA SCRIPTS #}
<script src="https://www.google.com/recaptcha/api.js?render=<public_key>"></script>
<script>
grecaptcha.ready(function() {
    grecaptcha.execute("<public_key>", {
        action: "/user_register"
    }).then(function(token) {
        document.getElementById('g-recaptcha-response').value = token;
    })
})
</script>

```  

Rajouter enfin une couche vérification dans la méthode register en renseignant la clé privé  
```
            $recaptcha = new \ReCaptcha\ReCaptcha('<private_key>');
            $resp = $recaptcha->verify($_POST['g-recaptcha-response']);

            if ($resp->isSuccess()) 
            {
            
            } else {
               $errors = $resp->getErrorCodes();
            }

```
