<?php
namespace Provider\Lv1;
/**
 * Autogenerated by Thrift Compiler (0.9.1)
 *
 * DO NOT EDIT UNLESS YOU ARE SURE THAT YOU KNOW WHAT YOU ARE DOING
 *  @generated
 */
use Thrift\Base\TBase;
use Thrift\Type\TType;
use Thrift\Type\TMessageType;
use Thrift\Exception\TException;
use Thrift\Exception\TProtocolException;
use Thrift\Protocol\TProtocol;
use Thrift\Protocol\TBinaryProtocolAccelerated;
use Thrift\Exception\TApplicationException;


interface LiveThriftServiceIf {
  public function getLiveList($uid, $platform, $version);
  public function getAllLiveList($uid, $max, $size, $type, $platform, $version);
}

class LiveThriftServiceClient implements \Provider\Lv1\LiveThriftServiceIf {
  protected $input_ = null;
  protected $output_ = null;

  protected $seqid_ = 0;

  public function __construct($input, $output=null) {
    $this->input_ = $input;
    $this->output_ = $output ? $output : $input;
  }

  public function getLiveList($uid, $platform, $version)
  {
    $this->send_getLiveList($uid, $platform, $version);
    return $this->recv_getLiveList();
  }

  public function send_getLiveList($uid, $platform, $version)
  {
    $args = new \Provider\Lv1\LiveThriftService_getLiveList_args();
    $args->uid = $uid;
    $args->platform = $platform;
    $args->version = $version;
    $bin_accel = ($this->output_ instanceof TBinaryProtocolAccelerated) && function_exists('thrift_protocol_write_binary');
    if ($bin_accel)
    {
      thrift_protocol_write_binary($this->output_, 'getLiveList', TMessageType::CALL, $args, $this->seqid_, $this->output_->isStrictWrite());
    }
    else
    {
      $this->output_->writeMessageBegin('getLiveList', TMessageType::CALL, $this->seqid_);
      $args->write($this->output_);
      $this->output_->writeMessageEnd();
      $this->output_->getTransport()->flush();
    }
  }

  public function recv_getLiveList()
  {
    $bin_accel = ($this->input_ instanceof TBinaryProtocolAccelerated) && function_exists('thrift_protocol_read_binary');
    if ($bin_accel) $result = thrift_protocol_read_binary($this->input_, '\Provider\Lv1\LiveThriftService_getLiveList_result', $this->input_->isStrictRead());
    else
    {
      $rseqid = 0;
      $fname = null;
      $mtype = 0;

      $this->input_->readMessageBegin($fname, $mtype, $rseqid);
      if ($mtype == TMessageType::EXCEPTION) {
        $x = new TApplicationException();
        $x->read($this->input_);
        $this->input_->readMessageEnd();
        throw $x;
      }
      $result = new \Provider\Lv1\LiveThriftService_getLiveList_result();
      $result->read($this->input_);
      $this->input_->readMessageEnd();
    }
    if ($result->success !== null) {
      return $result->success;
    }
    throw new \Exception("getLiveList failed: unknown result");
  }

  public function getAllLiveList($uid, $max, $size, $type, $platform, $version)
  {
    $this->send_getAllLiveList($uid, $max, $size, $type, $platform, $version);
    return $this->recv_getAllLiveList();
  }

  public function send_getAllLiveList($uid, $max, $size, $type, $platform, $version)
  {
    $args = new \Provider\Lv1\LiveThriftService_getAllLiveList_args();
    $args->uid = $uid;
    $args->max = $max;
    $args->size = $size;
    $args->type = $type;
    $args->platform = $platform;
    $args->version = $version;
    $bin_accel = ($this->output_ instanceof TBinaryProtocolAccelerated) && function_exists('thrift_protocol_write_binary');
    if ($bin_accel)
    {
      thrift_protocol_write_binary($this->output_, 'getAllLiveList', TMessageType::CALL, $args, $this->seqid_, $this->output_->isStrictWrite());
    }
    else
    {
      $this->output_->writeMessageBegin('getAllLiveList', TMessageType::CALL, $this->seqid_);
      $args->write($this->output_);
      $this->output_->writeMessageEnd();
      $this->output_->getTransport()->flush();
    }
  }

  public function recv_getAllLiveList()
  {
    $bin_accel = ($this->input_ instanceof TBinaryProtocolAccelerated) && function_exists('thrift_protocol_read_binary');
    if ($bin_accel) $result = thrift_protocol_read_binary($this->input_, '\Provider\Lv1\LiveThriftService_getAllLiveList_result', $this->input_->isStrictRead());
    else
    {
      $rseqid = 0;
      $fname = null;
      $mtype = 0;

      $this->input_->readMessageBegin($fname, $mtype, $rseqid);
      if ($mtype == TMessageType::EXCEPTION) {
        $x = new TApplicationException();
        $x->read($this->input_);
        $this->input_->readMessageEnd();
        throw $x;
      }
      $result = new \Provider\Lv1\LiveThriftService_getAllLiveList_result();
      $result->read($this->input_);
      $this->input_->readMessageEnd();
    }
    if ($result->success !== null) {
      return $result->success;
    }
    throw new \Exception("getAllLiveList failed: unknown result");
  }

}

// HELPER FUNCTIONS AND STRUCTURES

class LiveThriftService_getLiveList_args {
  static $_TSPEC;

  public $uid = null;
  public $platform = null;
  public $version = null;

  public function __construct($vals=null) {
    if (!isset(self::$_TSPEC)) {
      self::$_TSPEC = array(
        1 => array(
          'var' => 'uid',
          'type' => TType::STRING,
          ),
        2 => array(
          'var' => 'platform',
          'type' => TType::STRING,
          ),
        3 => array(
          'var' => 'version',
          'type' => TType::STRING,
          ),
        );
    }
    if (is_array($vals)) {
      if (isset($vals['uid'])) {
        $this->uid = $vals['uid'];
      }
      if (isset($vals['platform'])) {
        $this->platform = $vals['platform'];
      }
      if (isset($vals['version'])) {
        $this->version = $vals['version'];
      }
    }
  }

  public function getName() {
    return 'LiveThriftService_getLiveList_args';
  }

  public function read($input)
  {
    $xfer = 0;
    $fname = null;
    $ftype = 0;
    $fid = 0;
    $xfer += $input->readStructBegin($fname);
    while (true)
    {
      $xfer += $input->readFieldBegin($fname, $ftype, $fid);
      if ($ftype == TType::STOP) {
        break;
      }
      switch ($fid)
      {
        case 1:
          if ($ftype == TType::STRING) {
            $xfer += $input->readString($this->uid);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 2:
          if ($ftype == TType::STRING) {
            $xfer += $input->readString($this->platform);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 3:
          if ($ftype == TType::STRING) {
            $xfer += $input->readString($this->version);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        default:
          $xfer += $input->skip($ftype);
          break;
      }
      $xfer += $input->readFieldEnd();
    }
    $xfer += $input->readStructEnd();
    return $xfer;
  }

  public function write($output) {
    $xfer = 0;
    $xfer += $output->writeStructBegin('LiveThriftService_getLiveList_args');
    if ($this->uid !== null) {
      $xfer += $output->writeFieldBegin('uid', TType::STRING, 1);
      $xfer += $output->writeString($this->uid);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->platform !== null) {
      $xfer += $output->writeFieldBegin('platform', TType::STRING, 2);
      $xfer += $output->writeString($this->platform);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->version !== null) {
      $xfer += $output->writeFieldBegin('version', TType::STRING, 3);
      $xfer += $output->writeString($this->version);
      $xfer += $output->writeFieldEnd();
    }
    $xfer += $output->writeFieldStop();
    $xfer += $output->writeStructEnd();
    return $xfer;
  }

}

class LiveThriftService_getLiveList_result {
  static $_TSPEC;

  public $success = null;

  public function __construct($vals=null) {
    if (!isset(self::$_TSPEC)) {
      self::$_TSPEC = array(
        0 => array(
          'var' => 'success',
          'type' => TType::STRING,
          ),
        );
    }
    if (is_array($vals)) {
      if (isset($vals['success'])) {
        $this->success = $vals['success'];
      }
    }
  }

  public function getName() {
    return 'LiveThriftService_getLiveList_result';
  }

  public function read($input)
  {
    $xfer = 0;
    $fname = null;
    $ftype = 0;
    $fid = 0;
    $xfer += $input->readStructBegin($fname);
    while (true)
    {
      $xfer += $input->readFieldBegin($fname, $ftype, $fid);
      if ($ftype == TType::STOP) {
        break;
      }
      switch ($fid)
      {
        case 0:
          if ($ftype == TType::STRING) {
            $xfer += $input->readString($this->success);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        default:
          $xfer += $input->skip($ftype);
          break;
      }
      $xfer += $input->readFieldEnd();
    }
    $xfer += $input->readStructEnd();
    return $xfer;
  }

  public function write($output) {
    $xfer = 0;
    $xfer += $output->writeStructBegin('LiveThriftService_getLiveList_result');
    if ($this->success !== null) {
      $xfer += $output->writeFieldBegin('success', TType::STRING, 0);
      $xfer += $output->writeString($this->success);
      $xfer += $output->writeFieldEnd();
    }
    $xfer += $output->writeFieldStop();
    $xfer += $output->writeStructEnd();
    return $xfer;
  }

}

class LiveThriftService_getAllLiveList_args {
  static $_TSPEC;

  public $uid = null;
  public $max = null;
  public $size = null;
  public $type = null;
  public $platform = null;
  public $version = null;

  public function __construct($vals=null) {
    if (!isset(self::$_TSPEC)) {
      self::$_TSPEC = array(
        1 => array(
          'var' => 'uid',
          'type' => TType::STRING,
          ),
        2 => array(
          'var' => 'max',
          'type' => TType::STRING,
          ),
        3 => array(
          'var' => 'size',
          'type' => TType::STRING,
          ),
        4 => array(
          'var' => 'type',
          'type' => TType::STRING,
          ),
        5 => array(
          'var' => 'platform',
          'type' => TType::STRING,
          ),
        6 => array(
          'var' => 'version',
          'type' => TType::STRING,
          ),
        );
    }
    if (is_array($vals)) {
      if (isset($vals['uid'])) {
        $this->uid = $vals['uid'];
      }
      if (isset($vals['max'])) {
        $this->max = $vals['max'];
      }
      if (isset($vals['size'])) {
        $this->size = $vals['size'];
      }
      if (isset($vals['type'])) {
        $this->type = $vals['type'];
      }
      if (isset($vals['platform'])) {
        $this->platform = $vals['platform'];
      }
      if (isset($vals['version'])) {
        $this->version = $vals['version'];
      }
    }
  }

  public function getName() {
    return 'LiveThriftService_getAllLiveList_args';
  }

  public function read($input)
  {
    $xfer = 0;
    $fname = null;
    $ftype = 0;
    $fid = 0;
    $xfer += $input->readStructBegin($fname);
    while (true)
    {
      $xfer += $input->readFieldBegin($fname, $ftype, $fid);
      if ($ftype == TType::STOP) {
        break;
      }
      switch ($fid)
      {
        case 1:
          if ($ftype == TType::STRING) {
            $xfer += $input->readString($this->uid);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 2:
          if ($ftype == TType::STRING) {
            $xfer += $input->readString($this->max);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 3:
          if ($ftype == TType::STRING) {
            $xfer += $input->readString($this->size);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 4:
          if ($ftype == TType::STRING) {
            $xfer += $input->readString($this->type);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 5:
          if ($ftype == TType::STRING) {
            $xfer += $input->readString($this->platform);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 6:
          if ($ftype == TType::STRING) {
            $xfer += $input->readString($this->version);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        default:
          $xfer += $input->skip($ftype);
          break;
      }
      $xfer += $input->readFieldEnd();
    }
    $xfer += $input->readStructEnd();
    return $xfer;
  }

  public function write($output) {
    $xfer = 0;
    $xfer += $output->writeStructBegin('LiveThriftService_getAllLiveList_args');
    if ($this->uid !== null) {
      $xfer += $output->writeFieldBegin('uid', TType::STRING, 1);
      $xfer += $output->writeString($this->uid);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->max !== null) {
      $xfer += $output->writeFieldBegin('max', TType::STRING, 2);
      $xfer += $output->writeString($this->max);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->size !== null) {
      $xfer += $output->writeFieldBegin('size', TType::STRING, 3);
      $xfer += $output->writeString($this->size);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->type !== null) {
      $xfer += $output->writeFieldBegin('type', TType::STRING, 4);
      $xfer += $output->writeString($this->type);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->platform !== null) {
      $xfer += $output->writeFieldBegin('platform', TType::STRING, 5);
      $xfer += $output->writeString($this->platform);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->version !== null) {
      $xfer += $output->writeFieldBegin('version', TType::STRING, 6);
      $xfer += $output->writeString($this->version);
      $xfer += $output->writeFieldEnd();
    }
    $xfer += $output->writeFieldStop();
    $xfer += $output->writeStructEnd();
    return $xfer;
  }

}

class LiveThriftService_getAllLiveList_result {
  static $_TSPEC;

  public $success = null;

  public function __construct($vals=null) {
    if (!isset(self::$_TSPEC)) {
      self::$_TSPEC = array(
        0 => array(
          'var' => 'success',
          'type' => TType::STRING,
          ),
        );
    }
    if (is_array($vals)) {
      if (isset($vals['success'])) {
        $this->success = $vals['success'];
      }
    }
  }

  public function getName() {
    return 'LiveThriftService_getAllLiveList_result';
  }

  public function read($input)
  {
    $xfer = 0;
    $fname = null;
    $ftype = 0;
    $fid = 0;
    $xfer += $input->readStructBegin($fname);
    while (true)
    {
      $xfer += $input->readFieldBegin($fname, $ftype, $fid);
      if ($ftype == TType::STOP) {
        break;
      }
      switch ($fid)
      {
        case 0:
          if ($ftype == TType::STRING) {
            $xfer += $input->readString($this->success);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        default:
          $xfer += $input->skip($ftype);
          break;
      }
      $xfer += $input->readFieldEnd();
    }
    $xfer += $input->readStructEnd();
    return $xfer;
  }

  public function write($output) {
    $xfer = 0;
    $xfer += $output->writeStructBegin('LiveThriftService_getAllLiveList_result');
    if ($this->success !== null) {
      $xfer += $output->writeFieldBegin('success', TType::STRING, 0);
      $xfer += $output->writeString($this->success);
      $xfer += $output->writeFieldEnd();
    }
    $xfer += $output->writeFieldStop();
    $xfer += $output->writeStructEnd();
    return $xfer;
  }

}

class LiveThriftServiceProcessor {
  protected $handler_ = null;
  public function __construct($handler) {
    $this->handler_ = $handler;
  }

  public function process($input, $output) {
    $rseqid = 0;
    $fname = null;
    $mtype = 0;

    $input->readMessageBegin($fname, $mtype, $rseqid);
    $methodname = 'process_'.$fname;
    if (!method_exists($this, $methodname)) {
      $input->skip(TType::STRUCT);
      $input->readMessageEnd();
      $x = new TApplicationException('Function '.$fname.' not implemented.', TApplicationException::UNKNOWN_METHOD);
      $output->writeMessageBegin($fname, TMessageType::EXCEPTION, $rseqid);
      $x->write($output);
      $output->writeMessageEnd();
      $output->getTransport()->flush();
      return;
    }
    $this->$methodname($rseqid, $input, $output);
    return true;
  }

  protected function process_getLiveList($seqid, $input, $output) {
    $args = new \Provider\Lv1\LiveThriftService_getLiveList_args();
    $args->read($input);
    $input->readMessageEnd();
    $result = new \Provider\Lv1\LiveThriftService_getLiveList_result();
    $result->success = $this->handler_->getLiveList($args->uid, $args->platform, $args->version);
    $bin_accel = ($output instanceof TBinaryProtocolAccelerated) && function_exists('thrift_protocol_write_binary');
    if ($bin_accel)
    {
      thrift_protocol_write_binary($output, 'getLiveList', TMessageType::REPLY, $result, $seqid, $output->isStrictWrite());
    }
    else
    {
      $output->writeMessageBegin('getLiveList', TMessageType::REPLY, $seqid);
      $result->write($output);
      $output->writeMessageEnd();
      $output->getTransport()->flush();
    }
  }
  protected function process_getAllLiveList($seqid, $input, $output) {
    $args = new \Provider\Lv1\LiveThriftService_getAllLiveList_args();
    $args->read($input);
    $input->readMessageEnd();
    $result = new \Provider\Lv1\LiveThriftService_getAllLiveList_result();
    $result->success = $this->handler_->getAllLiveList($args->uid, $args->max, $args->size, $args->type, $args->platform, $args->version);
    $bin_accel = ($output instanceof TBinaryProtocolAccelerated) && function_exists('thrift_protocol_write_binary');
    if ($bin_accel)
    {
      thrift_protocol_write_binary($output, 'getAllLiveList', TMessageType::REPLY, $result, $seqid, $output->isStrictWrite());
    }
    else
    {
      $output->writeMessageBegin('getAllLiveList', TMessageType::REPLY, $seqid);
      $result->write($output);
      $output->writeMessageEnd();
      $output->getTransport()->flush();
    }
  }
}

