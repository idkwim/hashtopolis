<?php


use DBA\ContainFilter;
use DBA\CrackerBinary;
use DBA\CrackerBinaryType;
use DBA\QueryFilter;
use DBA\Task;

class CrackerHandler implements Handler {
  public function __construct($hashcatId = null) {
    //nothing
  }
  
  public function handle($action) {
    global $ACCESS_CONTROL;
    
    switch ($action) {
      case DCrackerBinaryAction::DELETE_BINARY_TYPE:
        $ACCESS_CONTROL->checkPermission(DCrackerBinaryAction::DELETE_BINARY_TYPE_PERM);
        $this->deleteBinaryType($_POST['binaryTypeId']);
        break;
      case DCrackerBinaryAction::DELETE_BINARY:
        $ACCESS_CONTROL->checkPermission(DCrackerBinaryAction::DELETE_BINARY_PERM);
        $this->deleteBinary($_POST['binaryId']);
        break;
      case DCrackerBinaryAction::CREATE_BINARY_TYPE:
        $ACCESS_CONTROL->checkPermission(DCrackerBinaryAction::CREATE_BINARY_TYPE_PERM);
        $this->createBinaryType($_POST['name']);
        break;
      case DCrackerBinaryAction::CREATE_BINARY:
        $ACCESS_CONTROL->checkPermission(DCrackerBinaryAction::CREATE_BINARY_PERM);
        $this->createBinary($_POST['version'], $_POST['name'], $_POST['url'], $_POST['binaryTypeId']);
        break;
      case DCrackerBinaryAction::EDIT_BINARY:
        $ACCESS_CONTROL->checkPermission(DCrackerBinaryAction::EDIT_BINARY_PERM);
        $this->updateBinary($_POST['version'], $_POST['name'], $_POST['url'], $_POST['binaryId']);
        break;
      default:
        UI::addMessage(UI::ERROR, "Invalid action!");
        break;
    }
  }
  
  private function updateBinary($version, $name, $url, $binaryId) {
    global $FACTORIES;
    
    $binary = $FACTORIES::getCrackerBinaryFactory()->get($binaryId);
    if ($binary === null) {
      UI::addMessage(UI::ERROR, "Invalid cracker binary!");
      return;
    }
    else if (strlen($version) == 0 || strlen($name) == 0 || strlen($url) == 0) {
      UI::addMessage(UI::ERROR, "Please provide all information!");
      return;
    }
    $binary->setBinaryName(htmlentities($name, ENT_QUOTES, "UTF-8"));
    $binary->setDownloadUrl($url);
    $binary->setVersion($version);
    $FACTORIES::getCrackerBinaryFactory()->update($binary);
    UI::addMessage(UI::SUCCESS, "Version was updated successfully!");
    $binaryType = $FACTORIES::getCrackerBinaryTypeFactory()->get($binary->getCrackerBinaryTypeId());
    header("Location: crackers.php?id=" . $binaryType->getId());
    die();
  }
  
  private function deleteBinaryType($binaryTypeId) {
    global $FACTORIES;
    
    $binaryType = $FACTORIES::getCrackerBinaryTypeFactory()->get($binaryTypeId);
    if ($binaryType === null) {
      UI::addMessage(UI::ERROR, "Invalid binary type!");
      return;
    }
    
    $qF = new QueryFilter(CrackerBinary::CRACKER_BINARY_TYPE_ID, $binaryType->getId(), "=");
    $binaries = $FACTORIES::getCrackerBinaryFactory()->filter(array($FACTORIES::FILTER => $qF));
    $versionIds = Util::arrayOfIds($binaries);
    
    $qF = new ContainFilter(Task::CRACKER_BINARY_ID, $versionIds);
    $check = $FACTORIES::getTaskFactory()->filter(array($FACTORIES::FILTER => $qF));
    if (sizeof($check) > 0) {
      UI::addMessage(UI::ERROR, "There are tasks which use binaries of this cracker!");
      return;
    }
    
    // delete
    $FACTORIES::getCrackerBinaryFactory()->massDeletion(array($FACTORIES::FILTER => $qF));
    $FACTORIES::getCrackerBinaryTypeFactory()->delete($binaryType);
    header("Location: crackers.php");
    die();
  }
  
  private function deleteBinary($binaryId) {
    global $FACTORIES;
    
    $binary = $FACTORIES::getCrackerBinaryFactory()->get($binaryId);
    if ($binary === null) {
      UI::addMessage(UI::ERROR, "Invalid version!");
      return;
    }
    $qF = new QueryFilter(Task::CRACKER_BINARY_ID, $binary->getId(), "=");
    $check = $FACTORIES::getTaskFactory()->filter(array($FACTORIES::FILTER => $qF));
    if (sizeof($check) > 0) {
      UI::addMessage(UI::ERROR, "There are tasks which use this binary!");
      return;
    }
    $FACTORIES::getCrackerBinaryFactory()->delete($binary);
  }
  
  private function createBinary($version, $name, $url, $binaryTypeId) {
    global $FACTORIES;
    
    $binaryType = $FACTORIES::getCrackerBinaryTypeFactory()->get($binaryTypeId);
    if ($binaryType === null) {
      UI::addMessage(UI::ERROR, "Invalid cracker binary type!");
      return;
    }
    else if (strlen($version) == 0 || strlen($name) == 0 || strlen($url) == 0) {
      UI::addMessage(UI::ERROR, "Please provide all information!");
      return;
    }
    $binary = new CrackerBinary(0, $binaryType->getId(), $version, $url, $name);
    $FACTORIES::getCrackerBinaryFactory()->save($binary);
    UI::addMessage(UI::SUCCESS, "Version was created successfully!");
    header("Location: crackers.php?id=" . $binaryType->getId());
    die();
  }
  
  private function createBinaryType($typeName) {
    global $FACTORIES;
    
    $qF = new QueryFilter(CrackerBinaryType::TYPE_NAME, $typeName, "=");
    $check = $FACTORIES::getCrackerBinaryTypeFactory()->filter(array($FACTORIES::FILTER => $qF), true);
    if ($check !== null) {
      UI::addMessage(UI::ERROR, "This binary type already exists!");
      return;
    }
    $binaryType = new CrackerBinaryType(0, $typeName, 1);
    $FACTORIES::getCrackerBinaryTypeFactory()->save($binaryType);
    header("Location: crackers.php");
    die();
  }
}