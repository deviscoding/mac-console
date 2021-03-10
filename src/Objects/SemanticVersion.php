<?php

namespace DevCoding\Mac\Objects;

use PHLAK\SemVer\Version;

class SemanticVersion extends Version
{
  public function __construct(string $version = '0.1.0')
  {
    try
    {
      parent::__construct($version);
    }
    catch (\Exception $e)
    {
      $Fix = static::parse($version);

      $this->setMajor($Fix->major);
      $this->setMinor($Fix->minor);
      $this->setPatch($Fix->patch);
      $this->setPreRelease($Fix->preRelease);
      $this->setbuild($Fix->build);
    }
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
