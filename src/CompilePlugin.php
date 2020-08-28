<?php

namespace Civi\CompilePlugin;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\Installer\PackageEvent;
use Composer\Installer\PackageEvents;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Script\Event;
use Composer\Script\ScriptEvents;

class CompilePlugin implements PluginInterface, EventSubscriberInterface {

  /**
   * @var \Composer\Composer*/
  private $composer;

  /**
   * @var \Composer\IO\IOInterface*/
  private $io;

  private $parser;

  public static function getSubscribedEvents() {
    return [
      PackageEvents::POST_PACKAGE_INSTALL => ['installDownloads', 10],
      PackageEvents::POST_PACKAGE_UPDATE => ['updateDownloads', 10],
      ScriptEvents::POST_INSTALL_CMD => ['installDownloadsRoot', 10],
      ScriptEvents::POST_UPDATE_CMD => ['installDownloadsRoot', 10],
    ];
  }

  public function activate(Composer $composer, IOInterface $io) {
    $this->composer = $composer;
    $this->io = $io;
  }

  public function installDownloadsRoot(Event $event) {
    $rootPackage = $this->composer->getPackage();
    $localRepo = $this->composer->getRepositoryManager()->getLocalRepository();
    $installationManager = $this->composer->getInstallationManager();
    foreach ($localRepo->getCanonicalPackages() as $package) {
      /** @var \Composer\Package\PackageInterface $package */
      // $package->getExtra()
      // $installationManager->getInstallPath($package)
    }
  }

  public function installDownloads(PackageEvent $event) {
    /** @var \Composer\Package\PackageInterface $package */
    $package = $event->getOperation()->getPackage();
    $installationManager = $event->getComposer()->getInstallationManager();
    // $installationManager->getInstallPath($package);
  }

  public function updateDownloads(PackageEvent $event) {
    /** @var \Composer\Package\PackageInterface $package */
    $package = $event->getOperation()->getTargetPackage();
    $installationManager = $event->getComposer()->getInstallationManager();
    // $installationManager->getInstallPath($package);
  }

}
