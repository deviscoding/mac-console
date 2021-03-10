<?php

namespace DevCoding\Mac\Objects;

class MacOsVersion extends SemanticVersion
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

  public function __construct($version = '0.1.0', $build = null)
  {
    parent::__construct($version);

    if (!empty($build))
    {
      $this->setBuild($build);
    }
  }

  /**
   * Override to remove build and pre-release from string version.
   *
   * @return string Current version string
   */
  public function __toString(): string
  {
    return implode('.', [$this->major, $this->minor, $this->patch]);
  }

  public function getName()
  {
    $major = (string) $this->getMajor();
    $minor = (string) $this->getMinor();

    return !empty(self::NAMES[$major][$minor]) ? self::NAMES[$major][$minor] : null;
  }
}
