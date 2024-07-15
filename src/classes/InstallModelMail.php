<?php

/**
 * Class InstallModelMail
 */
class InstallModelMail extends InstallAbstractModel {

    // @codingStandardsIgnoreStart
    /** @var bool $smtp_checked */
    public $smtp_checked;
    /** @var string $server */
    public $server;
    /** @var string $login */
    public $login;
    /** @var string $password */
    public $password;
    /** @var int $port */
    public $port;
    /** @var string $encryption */
    public $encryption;
    /** @var string $email */
    public $email;
    // @codingStandardsIgnoreEnd

    /**
     * @param bool $smtpChecked
     * @param string $server
     * @param string $login
     * @param string $password
     * @param int $port
     * @param string $encryption
     * @param string $email
     * @throws PhenyxInstallerException
     */
    public function __construct($smtpChecked, $server, $login, $password, $port, $encryption, $email) {

        parent::__construct();

        $this->smtp_checked = $smtpChecked;
        $this->server = $server;
        $this->login = $login;
        $this->password = $password;
        $this->port = $port;
        $this->encryption = $encryption;
        $this->email = $email;
    }

    /**
     * Send a mail
     *
     * @param string $subject
     * @param string $content
     * @return bool|string false is everything was fine, or error string
     * @throws PhenyxException
     */
    public function send($subject, $content) {

        try {
            // Test with custom SMTP connection

            if ($this->smtp_checked) {
                // Retrocompatibility

                if (mb_strtolower($this->encryption) === 'off') {
                    $this->encryption = false;
                }

                $smtp = Swift_SmtpTransport::newInstance($this->server, $this->port, $this->encryption);
                $smtp->setUsername($this->login);
                $smtp->setpassword($this->password);
                $smtp->setTimeout(5);
                $swift = Swift_Mailer::newInstance($smtp);
            } else {
                // Test with normal PHP mail() call
                $swift = Swift_Mailer::newInstance(Swift_MailTransport::newInstance());
            }

            $message = Swift_Message::newInstance();

            $message
                ->setFrom($this->email)
                ->setTo('no-reply@' . Tools::getHttpHost(false, false, true))
                ->setSubject($subject)
                ->setBody($content);
            $message = new Swift_Message($subject, $content, 'text/html');

            if (@$swift->send($message)) {
                $result = true;
            } else {
                $result = 'Could not send message';
            }

            $swift->disconnect();
        } catch (Swift_SwiftException $e) {
            $result = $e->getMessage();
        }

        return $result;
    }

}
