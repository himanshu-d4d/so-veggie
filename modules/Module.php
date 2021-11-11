<?php
namespace modules;
use yii\base\Event;
use craft\events\ModelEvent;
use craft\elements\User;
use craft\mail\Message;

use Craft;

/**
 * Custom module class.
 *
 * This class will be available throughout the system via:
 * `Craft::$app->getModule('my-module')`.
 *
 * You can change its module ID ("my-module") to something else from
 * config/app.php.
 *
 * If you want the module to get loaded on every request, uncomment this line
 * in config/app.php:
 *
 *     'bootstrap' => ['my-module']
 *
 * Learn more about Yii module development in Yii's documentation:
 * http://www.yiiframework.com/doc-2.0/guide-structure-modules.html
 */
class Module extends \yii\base\Module
{
    /**
     * Initializes the module.
     */
    public function init()
    {
        // Set a @modules alias pointed to the modules/ directory
        Craft::setAlias('@modules', __DIR__);

        // Set the controllerNamespace based on whether this is a console or web request
        if (Craft::$app->getRequest()->getIsConsoleRequest()) {
            $this->controllerNamespace = 'modules\\console\\controllers';
        } else {
            $this->controllerNamespace = 'modules\\controllers';
        }

        parent::init();

        // Custom initialization code goes here...
        $this->NewUserNotificationEmail();
        $this->NewUserNotificationEmailAdmin();

    }

 

   
 ////////////////////////////////////////////////////////////////////////////////////////////////
 ////////////////////////////////////////////////////////////////////////////////////////////////
  protected function NewUserNotificationEmail(){
        Event::on(User::class,User::EVENT_AFTER_SAVE, function (ModelEvent $event) {
           
            $isNewUser = $event->isNew;
 
            $user = $event->sender;
           
            if ($isNewUser) {
                // parpare email Configuration
                $subject= 'Welcome';
                $Html   = " Hi $user->firstName, <br/>".
                            " Thank you for registering your account: <br/>".
                            " we'll typically approve within one hour. If your request is urgent, please contact us at 630.748.8900.".      
                            "Thanks";
                             // Send Mail
                            if($this->sendUserMail($Html, $subject, $user->email)){
                                return true;
                  }
               
            }
        });

    }
    protected function NewUserNotificationEmailAdmin(){
        Event::on(User::class,User::EVENT_AFTER_SAVE, function (ModelEvent $event) {
            $isNewUser = $event->isNew;
            $user = $event->sender;
            if ($isNewUser) {
            $subject= 'A-rent new user registration';
            $Html   = " Hi Admin, <br/>".
                        " New user register on A-rent.com with under following details: <br/>".
                        " <b>Name :</b> " . $user->firstName . ' ' . $user->lastName ."<br/>".
                        " <b>Email:</b> " . $user->email ."<br/><br/>".
                        " for more details go to admin panel \n".
                        "<a href=". getenv('$DEFAULT_SITE_URL'). "/admin/users/".$user->id ."> Activate User Profile Now</a><br/>".      
                        "Thanks";
            // Send Mail
            if($this->sendUserMail($Html, $subject)){
                return true;
             }
          }
       });
    }
    
    private function sendUserMail($html, $subject, $sendToMail=null, array $attachments = array()): bool
    {

        $settings = Craft::$app->systemSettings->getSettings('email');
        
        if($sendToMail == null ){

            $sendToMail = empty(getenv('USER_NOTIFICATIONS_EMAIL'))? $settings['fromEmail'] : getenv('USER_NOTIFICATIONS_EMAIL') ;  
        }

        $message = new Message();

        $message->setFrom([$settings['fromEmail'] => $settings['fromName']]);
        $message->setTo($sendToMail);

        if(!empty( getenv('USER_NOTIFICATIONS_EMAIL_CC') )){
            $ccEmailsArray =  explode(',',trim(str_replace(" ", "", getenv('USER_NOTIFICATIONS_EMAIL_CC')) ) );
            $message->setCc($ccEmailsArray);
        }

        if(!empty( getenv('USER_NOTIFICATIONS_EMAIL_BCC') )){
            $bccEmailsArray =  explode(',',trim(str_replace(" ", "", getenv('USER_NOTIFICATIONS_EMAIL_BCC')) ) );
            $message->setBcc($bccEmailsArray);
        }
        
        $message->setSubject($subject);
        $message->setHtmlBody($html);
		
        if (!empty($attachments) && is_array($attachments)) {

            foreach ($attachments as $fileId) {
                if ($file = Craft::$app->assets->getAssetById((int)$fileId)) {
                    $message->attach($this->getFolderPath() . '/' . $file->filename, array(
                        'fileName' => $file->title . '.' . $file->getExtension()
                    ));
                }
            }
        }

        return Craft::$app->mailer->send($message);

 }

 
}