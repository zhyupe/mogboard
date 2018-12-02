<?php

namespace App\Services\User;

use App\Entity\User;
use App\Services\User\Discord\CsrfInvalidException;
use App\Services\User\Discord\DiscordSignIn;
use App\Services\User\SSO\SSOAccess;
use Delight\Cookie\Cookie;
use Doctrine\ORM\EntityManagerInterface;

class Users
{
    const COOKIE_SESSION_NAME = 'session';
    const COOKIE_SESSION_DURATION = (60 * 60 * 24 * 30);
    
    /** @var EntityManagerInterface */
    private $em;
    /** @var DiscordSignIn */
    private $sso;
    
    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }
    
    /**
     * Set the single sign in provider
     */
    public function setSsoProvider(SignInInterface $sso)
    {
        $this->sso = $sso;
        return $this;
    }
    
    /**
     * Get the current logged in user
     */
    public function getUser(): ?User
    {
        $session = Cookie::get(self::COOKIE_SESSION_NAME);
        if (!$session || $session === 'x') {
            return null;
        }
        
        /** @var User $user */
        $user = $this->em->getRepository(User::class)->findOneBy([
            'session' => $session
        ]);
        
        return $user;
    }
    
    public function isOnline()
    {
        return !empty($this->getUser());
    }
    
    /**
     * Sign in
     */
    public function login(): string
    {
        return $this->sso->getLoginAuthorizationUrl();
    }
    
    /**
     * Authenticate
     * @throws CsrfInvalidException
     */
    public function authenticate(): User
    {
        // look for their user if they already have an account
        $ssoAccess = $this->sso->setLoginAuthorizationState();
        $user = $this->em->getRepository(User::class)->findOneBy([
            'email' => $ssoAccess->email
        ]);
        
        // if they don't have an account, create one!
        if (!$user) {
            $user = $this->create($this->sso::NAME, $ssoAccess);
            // todo - send email?
        }
    
        $cookie = new Cookie(self::COOKIE_SESSION_NAME);
        $cookie->setValue($user->getSession())->setMaxAge(self::COOKIE_SESSION_DURATION)->setPath('/')->save();
        
        return $user;
    }
    
    /**
     * Logout a user
     */
    public function logout(): void
    {
        $cookie = new Cookie(self::COOKIE_SESSION_NAME);
        $cookie->setValue('x')->setMaxAge(-1)->setPath('/')->save();
        $cookie->delete();
    }
    
    /**
     * Create a new user
     */
    public function create(string $sso, SSOAccess $ssoAccess): User
    {
        $user = new User();
        $user
            ->setSso($sso)
            ->setToken(json_encode($ssoAccess))
            ->setUsername($ssoAccess->username)
            ->setEmail($ssoAccess->email)
            ->setAvatar($ssoAccess->avatar ?: 'http://xivapi.com/img-misc/chat_messengericon_goldsaucer.png');
        
        $this->save($user);
        return $user;
    }
    
    /**
     * Update a user
     */
    public function save(User $user): void
    {
        $this->em->persist($user);
        $this->em->flush();
    }
}