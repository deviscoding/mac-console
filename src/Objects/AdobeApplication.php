<?php

namespace DevCoding\Mac\Objects;

/**
 * Object representing an Adobe Creative Cloud application that is installed on macOS.
 *
 * @author  AMJones <am@jonesiscoding.com>
 * @license https://github.com/deviscoding/jss-helper/blob/main/LICENSE
 */
class AdobeApplication extends MacApplication
{
  const PATH_TEMPLATES = CreativeCloudHelper::PATH_TEMPLATES;
  const UNINSTALL      = '/Library/Application\ Support/Adobe/Adobe\ Desktop\ Common/HDBox/Setup --uninstall=1 --sapCode={sap} --baseVersion={version} --deleteUserPreferences=false --platform=osx10-64';

  /** @var SemanticVersion */
  protected $baseVersion;
  /** @var string */
  protected $name;
  /** @var string */
  protected $slug;
  /** @var string */
  protected $year;

  /**
   * @param string   $slug
   * @param int|null $year
   *
   * @return AdobeApplication|null
   */
  public static function fromSlug($slug, $year = null)
  {
    // Check Slug for Year
    if (empty($year) && preg_match('#([a-zA-Z-]+)-([0-9]+)#', $slug, $matches))
    {
      $year = $matches[2];
      $slug = $matches[1];
    }

    $Helper = new CreativeCloudHelper();
    foreach ($Helper->getInstalled($slug) as $path)
    {
      if (!$year || $year == $Helper->getYearFromPath($path))
      {
        return new AdobeApplication(dirname($path, 2));
      }
    }

    return null;
  }

  // region //////////////////////////////////////////////// Public Methods

  /**
   * @return SemanticVersion|false
   */
  public function getBaseVersion()
  {
    if (is_null($this->baseVersion))
    {
      $base = $this->getHelper()->getBaseVersions($this->getSlug());
      $year = $this->getYear();
      $name = $this->getBaseName();

      foreach ($base as $bVer => $bYear)
      {
        if ($bYear == $year || $bYear == $name)
        {
          return $this->baseVersion = new SemanticVersion($bVer);
        }
      }

      $this->baseVersion = false;
    }

    return $this->baseVersion;
  }

  /**
   * Returns an array of paths to this application's preferences.
   *
   * @return string[]
   */
  public function getPreferencePaths()
  {
    return $this->getHelper()->getPreferences($this);
  }

  /**
   * Returns the base name of the application, such as 'Photoshop' for 'Adobe Photoshop 2022'
   *
   * @return string
   */
  public function getProductName()
  {
    if (!isset($this->name))
    {
      if (!$this->name = $this->getHelper()->getName($this->getSlug()))
      {
        $this->name = $this->getHelper()->getNameFromPath($this->getPathname());
      }
    }

    return $this->name;
  }

  /**
   * Returns the SAP code for the application, as defined by Adobe and taken from cc.json.
   *
   * @return string
   */
  public function getSap()
  {
    if (!isset($this->sap))
    {
      $this->sap = $this->getHelper()->getSap($this->getSlug()) ?? false;
    }

    return $this->sap;
  }

  /**
   * Returns the slug for the application, such as 'photoshop' for 'Adobe Photoshop 2022'
   *
   * @return string|null
   */
  public function getSlug()
  {
    if (!isset($this->slug))
    {
      $this->slug = $this->getHelper()->getNameFromPath($this->getPathname());
    }

    return $this->slug;
  }

  /**
   * Returns a string for uninstalling the application from a Mac.
   *
   * @return string
   */
  public function getUninstall()
  {
    return str_replace(['{sap}', '{version}'], [$this->getSap(), $this->getBaseVersion()->getRaw()], self::UNINSTALL);
  }

  /**
   * If applicable, returns the year that is relevant to this Adobe Creative Cloud application.
   *
   * @return string|null
   */
  public function getYear()
  {
    if (!isset($this->year))
    {
      $this->year = $this->getHelper()->getYearFromPath($this->getPathname()) ?? false;
    }

    return $this->year;
  }

  /**
   * Evaluates whether CC is present in the application's name and path.
   *
   * @return bool
   */
  public function isCC()
  {
    return false !== strpos($this->getPathname(), 'CC');
  }

  // endregion ///////////////////////////////////////////// Public Methods

  // region //////////////////////////////////////////////// Helper Methods

  /**
   * @return CreativeCloudHelper
   */
  protected function getHelper()
  {
    return new CreativeCloudHelper();
  }

  // endregion ///////////////////////////////////////////// Helper Methods
}

