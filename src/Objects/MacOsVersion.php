<?php

namespace DevCoding\Mac\Objects;

use PHLAK\SemVer\Version;

class MacOsVersion extends Version
{
  const NAMES = [
      '10' => [
          '5'  => 'Leopard',
          '6'  => 'Snow Leopard',
          '7'  => 'Lion',
          '8'  => 'Mountain Lion',
          '9'  => 'Mavericks',
          '10' => 'Yosemite',
          '11' => 'El Capitan',
          '12' => 'Sierra',
          '13' => 'High Sierra',
          '14' => 'Mojave',
      ],
      '11' => [
          '0' => 'Big Sur',
      ],
  ];

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

  public function getName()
  {
    $major = (string) $this->getMajor();
    $minor = (string) $this->getMinor();

    return !empty(self::NAMES[$major][$minor]) ? self::NAMES[$major][$minor] : null;
  }
}
