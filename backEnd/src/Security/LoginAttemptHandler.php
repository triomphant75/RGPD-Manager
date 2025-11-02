<?php


use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Contracts\Cache\ItemInterface;

class LoginAttemptHandler
{
    private FilesystemAdapter $cache;
    
    public function __construct()
    {
        $this->cache = new FilesystemAdapter();
    }
    
    public function isBlocked(string $identifier): bool
    {
        $key = 'login_attempts_' . md5($identifier);
        $item = $this->cache->getItem($key);
        
        if (!$item->isHit()) {
            return false;
        }
        
        $attempts = $item->get();
        return $attempts['count'] >= 5;
    }
    
    public function recordAttempt(string $identifier): void
    {
        $key = 'login_attempts_' . md5($identifier);
        $item = $this->cache->getItem($key);
        
        $attempts = $item->isHit() ? $item->get() : ['count' => 0];
        $attempts['count']++;
        
        $item->set($attempts);
        $item->expiresAfter(900); // 15 minutes
        $this->cache->save($item);
    }
    
    public function resetAttempts(string $identifier): void
    {
        $key = 'login_attempts_' . md5($identifier);
        $this->cache->deleteItem($key);
    }
}