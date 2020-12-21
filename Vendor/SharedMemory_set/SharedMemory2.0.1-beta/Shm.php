<?php

/**
 * used to manage shared memory.
 * @author xudongw <xudongw@jumei.com>
 */

namespace SharedMemory;

class Shm {
    private $IPCKey = '';
    private $seqKey = '';
    private $semId  = false;
    private $shmId  = false;
    private $noWait = false;
    private $memsize;
    private $isLocked = false;

    public function __construct($IPCKey, $memsize = 10000, $seqKey = 8) {
        $this->IPCKey = $IPCKey;
        $this->memsize = $memsize;
        $this->seqKey = $seqKey;
    }

    public function __destruct()
    {
        if ($this->isLocked) {
            $this->unlockAndDettach();
        }
    }

    public function _isLocked(){
        return $this->isLocked;
    }
    
    public function setNoWait($noWait) {
        if (version_compare(PHP_VERSION, '5.6.1') < 0) {
            trigger_error("noWait param can only be effective above verison 5.6.1");
            return false;
        }
        $this->noWait = $noWait;
        return true;
    }

    public function setSeqKey($seqKey) {
        $this->seqKey = $seqKey;
    }

    public function hasVar() {
        return shm_has_var($this->shmId, $this->seqKey);
    }

    public function getVar() {
        if (!$this->isLocked) {
            throw new \Exception('get var should be called only after locking');
        }
        return shm_get_var($this->shmId, $this->seqKey);
    }


    public function getArrVar() {
        if (!$this->isLocked) {
            throw new \Exception('get var should be called only after locking');
        }
        if (!$this->hasVar()) {
            return array();
        }

        $var = shm_get_var($this->shmId, $this->seqKey);

        if (!is_array($var)) {
            return array();
        }
        return $var;
    }

    public function putVar($var) {
        if (!$this->isLocked) {
            throw new \Exception('put var should be called only after locking');
        }
        return shm_put_var($this->shmId, $this->seqKey, $var);
    }

    public function lockAndAttach() {
        $this->semId = sem_get($this->IPCKey);
        $this->shmId = shm_attach($this->IPCKey, $this->memsize);

        if (version_compare(PHP_VERSION, '5.6.1') >= 0) {
            $this->isLocked = sem_acquire($this->semId, $this->noWait);
        } else {
            $this->isLocked = sem_acquire($this->semId);
        }
        return $this->isLocked;
    }

    public function unlockAndDettach() {
        shm_detach($this->shmId);
        sem_release($this->semId);
        $this->isLocked = false;
    }
}