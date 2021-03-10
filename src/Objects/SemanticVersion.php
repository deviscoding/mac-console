<?php

namespace DevCoding\Mac\Objects;

use PHLAK\SemVer\Version;

class SemanticVersion extends Version
{
  protected $raw;

  public function __construct(string $version = '0.1.0')
  {
    $this->raw = $version;

    try
    {
      parent::__construct($version);
    }
    catch (\Exception $e)
    {
      try
      {
        // Try the built in fixer
        $Fix = static::parse($version);
      }
      catch (\Exception $e)
      {
        // Too many dots
        $v = explode('.', $version);
        if (count($v) > 3)
        {
          $version = array_shift($v) . "." . array_shift($v) . '.' . implode('', $v);
          $Fix = static::parse($version);
        }
      }

      $this->setMajor($Fix->major);
      $this->setMinor($Fix->minor);
      $this->setPatch($Fix->patch);
      $this->setPreRelease($Fix->preRelease);
      $this->setbuild($Fix->build);
    }
  }

  public function getRaw()
  {
    return $this->raw;
  }

  public function getBuild()
  {
    return $this->build;
  }

  public function getMajor()
  {
    return $this->major;
  }

  public function getMinor()
  {
    return $this->minor;
  }

  public function getRevision()
  {
    return $this->patch;
  }

  public function getPreRelease()
  {
    return $this->preRelease;
  }
}
