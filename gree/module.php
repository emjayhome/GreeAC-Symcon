<?php

abstract class GREECommands
{
    const none = '-1';
    const scan = '0';
    const bind = '1';
    const status = '2';
    const cmd = '3';
}

abstract class GREEDeviceParam
{
    const Power = "Pow";
    const Mode = "Mod";
    const Fanspeed = "WdSpd";
    const Swinger = "SwUpDn";
    const SwingerLeRi = "SwingLfRig";
    const SetTemperature = "SetTem";
    const ActTemperature = "TemSen";
    const OptXFan = "Blo";
    const OptHealth = "Health";
    const OptLight = "Lig";
    const OptSleep1 = "SwhSlp";
    const OptSleep2 = "SlpMod";
    const OptEco = "SvSt";
    const OptAir = "Air";
    const OptQuiet = "Quiet";
    const OptTurbo = "Tur";
}

abstract class GREELogType
{
    const INFO = 0;
    const ERROR = 10;
}

// Klassendefinition
class GREEACWIFI extends IPSModule {
    const defaultCryptKey = 'a3K8Bx%2r8Y7#xDh';
    const defaultGcmKey = '{yxAHAY_Lm6pbC/<';
    const GCM_IV = "5440784449675a516c5e6313"; // hex-encoded, 12 bytes
    const GCM_ADD = "qualcomm-test";

    // Der Konstruktor des Moduls
    // Überschreibt den Standard Kontruktor von IPS
    public function __construct($InstanceID) {
        // Diese Zeile nicht löschen
        parent::__construct($InstanceID);
    }

    // Überschreibt die interne IPS_Create($id) Funktion
    public function Create() {
        // Diese Zeile nicht löschen.
        parent::Create();

        $this->RegisterPropertyBoolean("active", false);
        $this->RegisterPropertyInteger("fanSteps", 3);
        $this->RegisterPropertyBoolean("swingLeRi", false);
        $this->RegisterPropertyBoolean("freshAir", false);
        $this->RegisterPropertyBoolean("autoLight", true);
        $this->RegisterPropertyBoolean("logInfo", false);
        $this->RegisterPropertyInteger("statusTimer", 60);
        $this->RegisterPropertyString("encryptionType", "ECB");
        $this->RegisterPropertyString("broadcastAddress", "192.168.6.255");

        $this->RegisterTimer("status_UpdateTimer", 0, 'GREE_getStatus($_IPS[\'TARGET\']);');
        $this->RegisterTimer("queue_WorkerTimer", 0, 'GREE_cmdQueueWorker($_IPS[\'TARGET\']);');

        $this->RequireParent("{82347F20-F541-41E1-AC5B-A636FD3AE2D8}");
    }

    // Überschreibt die intere IPS_ApplyChanges($id) Funktion
    public function ApplyChanges() {
        // Diese Zeile nicht löschen
        parent::ApplyChanges();

        $fanSteps = $this->ReadPropertyInteger("fanSteps");
        $hasSwingLeRi = $this->ReadPropertyBoolean("swingLeRi");
        $hasFreshAir = $this->ReadPropertyBoolean("freshAir");

        //Instanz ist aktiv
        if(!IPS_VariableProfileExists('GREE.DeviceMode'))
            IPS_CreateVariableProfile('GREE.DeviceMode', 1);
        if(!IPS_VariableProfileExists('GREE.DeviceFan3'))
            IPS_CreateVariableProfile('GREE.DeviceFan3', 1);
        if(!IPS_VariableProfileExists('GREE.DeviceFan5'))
            IPS_CreateVariableProfile('GREE.DeviceFan5', 1);
        if(!IPS_VariableProfileExists('GREE.DeviceFan6'))
            IPS_CreateVariableProfile('GREE.DeviceFan6', 1);
        if(!IPS_VariableProfileExists('GREE.DeviceFan7'))
            IPS_CreateVariableProfile('GREE.DeviceFan7', 1);
        if(!IPS_VariableProfileExists('GREE.DeviceSwinger'))
            IPS_CreateVariableProfile('GREE.DeviceSwinger', 1);
        if(!IPS_VariableProfileExists('GREE.DeviceSwingerLeRi'))
            IPS_CreateVariableProfile('GREE.DeviceSwingerLeRi', 1);
        if(!IPS_VariableProfileExists('GREE.SetTemp'))
            IPS_CreateVariableProfile('GREE.SetTemp', 1);
        if(!IPS_VariableProfileExists('GREE.ActTemp'))
            IPS_CreateVariableProfile('GREE.ActTemp', 1);

        IPS_SetVariableProfileAssociation('GREE.DeviceMode', 0, $this->Translate("paDeviceMode-0"), 'Climate', -1);
        IPS_SetVariableProfileAssociation('GREE.DeviceMode', 1, $this->Translate("paDeviceMode-1"), 'Snowflake', -1);
        IPS_SetVariableProfileAssociation('GREE.DeviceMode', 2, $this->Translate("paDeviceMode-2"), 'Drops', -1);
        IPS_SetVariableProfileAssociation('GREE.DeviceMode', 3, $this->Translate("paDeviceMode-3"), 'Ventilation', -1);
        IPS_SetVariableProfileAssociation('GREE.DeviceMode', 4, $this->Translate("paDeviceMode-4"), 'Flame', -1);

        IPS_SetVariableProfileAssociation('GREE.DeviceFan3', 0, $this->Translate("paDeviceFan-0"), 'Ventilation', -1);
        IPS_SetVariableProfileAssociation('GREE.DeviceFan3', 1, $this->Translate("paDeviceFan-1"), 'Speedo-0', -1);
        IPS_SetVariableProfileAssociation('GREE.DeviceFan3', 2, $this->Translate("paDeviceFan-2"), 'Speedo-50', -1);
        IPS_SetVariableProfileAssociation('GREE.DeviceFan3', 3, $this->Translate("paDeviceFan-3"), 'Speedo-100', -1);

        IPS_SetVariableProfileAssociation('GREE.DeviceFan5', 0, $this->Translate("paDeviceFan-0"), 'Ventilation', -1);
        IPS_SetVariableProfileAssociation('GREE.DeviceFan5', 1, $this->Translate("paDeviceFan-1"), 'Speedo-0', -1);
        IPS_SetVariableProfileAssociation('GREE.DeviceFan5', 2, $this->Translate("paDeviceFan-2"), 'Speedo-25', -1);
        IPS_SetVariableProfileAssociation('GREE.DeviceFan5', 3, $this->Translate("paDeviceFan-3"), 'Speedo-50', -1);
        IPS_SetVariableProfileAssociation('GREE.DeviceFan5', 4, $this->Translate("paDeviceFan-4"), 'Speedo-75', -1);
        IPS_SetVariableProfileAssociation('GREE.DeviceFan5', 5, $this->Translate("paDeviceFan-5"), 'Speedo-100', -1);

        IPS_SetVariableProfileAssociation('GREE.DeviceFan6', 0, $this->Translate("paDeviceFan-0"), 'Ventilation', -1);
        IPS_SetVariableProfileAssociation('GREE.DeviceFan6', 1, $this->Translate("paDeviceFan-1"), 'Speedo-0', -1);
        IPS_SetVariableProfileAssociation('GREE.DeviceFan6', 2, $this->Translate("paDeviceFan-2"), 'Speedo-25', -1);
        IPS_SetVariableProfileAssociation('GREE.DeviceFan6', 3, $this->Translate("paDeviceFan-3"), 'Speedo-50', -1);
        IPS_SetVariableProfileAssociation('GREE.DeviceFan6', 4, $this->Translate("paDeviceFan-4"), 'Speedo-50', -1);
        IPS_SetVariableProfileAssociation('GREE.DeviceFan6', 5, $this->Translate("paDeviceFan-5"), 'Speedo-75', -1);
        IPS_SetVariableProfileAssociation('GREE.DeviceFan6', 6, $this->Translate("paDeviceFan-6"), 'Speedo-100', -1);

        IPS_SetVariableProfileAssociation('GREE.DeviceFan7', 0, $this->Translate("paDeviceFan-0"), 'Ventilation', -1);
        IPS_SetVariableProfileAssociation('GREE.DeviceFan7', 1, $this->Translate("paDeviceFan-1"), 'Speedo-0', -1);
        IPS_SetVariableProfileAssociation('GREE.DeviceFan7', 2, $this->Translate("paDeviceFan-2"), 'Speedo-25', -1);
        IPS_SetVariableProfileAssociation('GREE.DeviceFan7', 3, $this->Translate("paDeviceFan-3"), 'Speedo-25', -1);
        IPS_SetVariableProfileAssociation('GREE.DeviceFan7', 4, $this->Translate("paDeviceFan-4"), 'Speedo-50', -1);
        IPS_SetVariableProfileAssociation('GREE.DeviceFan7', 5, $this->Translate("paDeviceFan-5"), 'Speedo-75', -1);
        IPS_SetVariableProfileAssociation('GREE.DeviceFan7', 6, $this->Translate("paDeviceFan-6"), 'Speedo-75', -1);
        IPS_SetVariableProfileAssociation('GREE.DeviceFan7', 7, $this->Translate("paDeviceFan-7"), 'Speedo-100', -1);

        IPS_SetVariableProfileAssociation('GREE.DeviceSwinger', 0, $this->Translate("paDeviceSwinger-0"), '', -1);
        IPS_SetVariableProfileAssociation('GREE.DeviceSwinger', 1, $this->Translate("paDeviceSwinger-1"), '', -1);
        IPS_SetVariableProfileAssociation('GREE.DeviceSwinger', 2, $this->Translate("paDeviceSwinger-2"), '', -1);
        IPS_SetVariableProfileAssociation('GREE.DeviceSwinger', 3, $this->Translate("paDeviceSwinger-3"), '', -1);
        IPS_SetVariableProfileAssociation('GREE.DeviceSwinger', 4, $this->Translate("paDeviceSwinger-4"), '', -1);
        IPS_SetVariableProfileAssociation('GREE.DeviceSwinger', 5, $this->Translate("paDeviceSwinger-5"), '', -1);
        IPS_SetVariableProfileAssociation('GREE.DeviceSwinger', 6, $this->Translate("paDeviceSwinger-6"), '', -1);
        IPS_SetVariableProfileAssociation('GREE.DeviceSwinger', 7, $this->Translate("paDeviceSwinger-7"), '', -1);
        IPS_SetVariableProfileAssociation('GREE.DeviceSwinger', 8, $this->Translate("paDeviceSwinger-8"), '', -1);
        IPS_SetVariableProfileAssociation('GREE.DeviceSwinger', 9, $this->Translate("paDeviceSwinger-9"), '', -1);
        IPS_SetVariableProfileAssociation('GREE.DeviceSwinger', 10, $this->Translate("paDeviceSwinger-10"), '', -1);
        IPS_SetVariableProfileAssociation('GREE.DeviceSwinger', 11, $this->Translate("paDeviceSwinger-11"), '', -1);

        IPS_SetVariableProfileAssociation('GREE.DeviceSwingerLeRi', 0, $this->Translate("paDeviceSwingerLeRi-0"), '', -1);
        IPS_SetVariableProfileAssociation('GREE.DeviceSwingerLeRi', 1, $this->Translate("paDeviceSwingerLeRi-1"), '', -1);
        IPS_SetVariableProfileAssociation('GREE.DeviceSwingerLeRi', 2, $this->Translate("paDeviceSwingerLeRi-2"), '', -1);
        IPS_SetVariableProfileAssociation('GREE.DeviceSwingerLeRi', 3, $this->Translate("paDeviceSwingerLeRi-3"), '', -1);
        IPS_SetVariableProfileAssociation('GREE.DeviceSwingerLeRi', 4, $this->Translate("paDeviceSwingerLeRi-4"), '', -1);
        IPS_SetVariableProfileAssociation('GREE.DeviceSwingerLeRi', 5, $this->Translate("paDeviceSwingerLeRi-5"), '', -1);
        IPS_SetVariableProfileAssociation('GREE.DeviceSwingerLeRi', 6, $this->Translate("paDeviceSwingerLeRi-6"), '', -1);

        IPS_SetVariableProfileValues('GREE.SetTemp', 17, 27, 1);
        IPS_SetVariableProfileText('GREE.SetTemp', '', '°C');

        IPS_SetVariableProfileText('GREE.ActTemp', '', '°C');

        $this->RegisterVariableString("name", $this->Translate("varName"), '', 1);
        $this->RegisterVariableBoolean("power", $this->Translate("varPower"), '~Switch', 2);
        $this->RegisterVariableInteger("mode", $this->Translate("varMode"), 'GREE.DeviceMode', 3);
        $this->RegisterVariableInteger("setTemp", $this->Translate("varSetTemp"), 'GREE.SetTemp', 4);
        $this->RegisterVariableInteger("fan", $this->Translate("varFan"), 'GREE.DeviceFan'.$fanSteps, 5);
        $this->RegisterVariableInteger("swinger", $this->Translate("varSwinger"), 'GREE.DeviceSwinger', 6);
        if($hasSwingLeRi)
            $this->RegisterVariableInteger("swingerLeRi", $this->Translate("varSwingerLeRi"), 'GREE.DeviceSwingerLeRi', 7);
        $this->RegisterVariableInteger("actTemp", $this->Translate("varActTemp"), 'GREE.ActTemp', 8);
        $this->RegisterVariableBoolean("optXFan", $this->Translate("varOptXFan"), '~Switch', 9);
        $this->RegisterVariableBoolean("optHealth", $this->Translate("varOptHealth"), '~Switch', 10);
        $this->RegisterVariableBoolean("optLight", $this->Translate("varOptLight"), '~Switch', 11);
        $this->RegisterVariableBoolean("optSleep", $this->Translate("varOptSleep"), '~Switch', 12);
        $this->RegisterVariableBoolean("optEco", $this->Translate("varOptEco"), '~Switch', 13);
        if($hasFreshAir)
            $this->RegisterVariableBoolean("optAir", $this->Translate("varOptAir"), '~Switch', 14);
        $this->RegisterVariableString("lastUpdate", $this->Translate("varLastUpdate"), '', 15);
        $this->RegisterVariableString("macAddress", $this->Translate("varMacAddress"), '', 16);
        $this->RegisterVariableString("deviceKey", $this->Translate("varDeviceKey"), '', 17);

        IPS_SetIcon($this->GetIDForIdent('power'), 'Power');
        IPS_SetIcon($this->GetIDForIdent('swinger'), 'WindSpeed');
        if($hasSwingLeRi)
            IPS_SetIcon($this->GetIDForIdent('swingerLeRi'), 'WindDirection');
        IPS_SetIcon($this->GetIDForIdent('setTemp'), 'Temperature');
        IPS_SetIcon($this->GetIDForIdent('actTemp'), 'Temperature');
        IPS_SetIcon($this->GetIDForIdent('optXFan'), 'TurnRight');
        IPS_SetIcon($this->GetIDForIdent('optHealth'), 'Tree');
        IPS_SetIcon($this->GetIDForIdent('optLight'), 'Light');
        IPS_SetIcon($this->GetIDForIdent('optSleep'), 'Moon');
        IPS_SetIcon($this->GetIDForIdent('optEco'), 'Leaf');
        if($hasFreshAir)
            IPS_SetIcon($this->GetIDForIdent('optAir'), 'WindDirection');
        IPS_SetIcon($this->GetIDForIdent('lastUpdate'), 'Repeat');
        IPS_SetIcon($this->GetIDForIdent('macAddress'), 'Notebook');

        $this->EnableAction("power");
        $this->EnableAction("mode");
        $this->EnableAction("fan");
        $this->EnableAction("swinger");
        if($hasSwingLeRi)
            $this->EnableAction("swingerLeRi");
        $this->EnableAction("setTemp");
        $this->EnableAction("optXFan");
        $this->EnableAction("optHealth");
        $this->EnableAction("optLight");
        $this->EnableAction("optSleep");
        $this->EnableAction("optEco");
        if($hasFreshAir)
            $this->EnableAction("optAir");

        IPS_SetHidden($this->GetIDForIdent('deviceKey'), true);

        $statusInterval = $this->ReadPropertyInteger("statusTimer");

        $this->SetBuffer("actualCommand", GREECommands::none);
        $this->SetBuffer('cmdQueue', serialize(array()));

        if($this->ReadPropertyBoolean("active")){
            $this->SetTimerInterval('status_UpdateTimer', $statusInterval*1000);
            $this->SetStatus(102);
        }else{
            $this->SetTimerInterval('status_UpdateTimer', 0);
            $this->SetStatus(104);
        }
    }

    public function GetConfigurationForParent(){
        $JsonArray = array( "Port" => 7000, "Open" => true);
        $Json = json_encode($JsonArray);
        return $Json;
    }


    public function RequestAction($Ident, $Value) {
        switch($Ident) {
            case 'power':
                $this->setPower($Value);
                break;
            case 'mode':
                $this->setMode($Value);
                break;
            case 'fan':
                $this->setFan($Value);
                break;
            case 'swinger':
                $this->setSwinger($Value);
                break;
            case 'swingerLeRi':
                $this->setSwingerLeRi($Value);
                break;
            case 'setTemp':
                $this->setTemp($Value);
                break;
            case 'optXFan':
                $this->setOptXFan($Value);
                break;
            case 'optHealth':
                $this->setOptHealth($Value);
                break;
            case 'optLight':
                $this->setOptLight($Value);
                break;
            case 'optSleep':
                $this->setOptSleep($Value);
                break;
            case 'optEco':
                $this->setOptEco($Value);
                break;
            case 'optAir':
                $this->setOptAir($Value);
                break;
            default:
                throw new Exception("Invalid ident");
        }

    }

    public function ReceiveData($JSONString){
        $this->log('ReceiveData', 'Received data: ' . $JSONString, GREELogType::INFO);
        $actCmd = $this->GetBuffer('actualCommand');
        $recObj = json_decode($JSONString);
        $bufferObj = json_decode($recObj->Buffer);
        $encryptionType = 'ECB';
        if (isset($bufferObj->tag)) {
            $encryptionType = 'GCM';
        }
        $this->log('ReceiveData', 'Encryption Type: ' . $encryptionType, GREELogType::INFO);
        // Default-Key je nach Modus
        if ($actCmd < GREECommands::status) {
            $key = ($encryptionType === 'GCM') ? self::defaultGcmKey : self::defaultCryptKey;
        } else {
            $key = GetValueString($this->GetIDForIdent('deviceKey'));
        }
        $this->log('ReceiveData', 'Using key: ' . $key, GREELogType::INFO);
        if ($encryptionType === 'GCM') {
            $decrypted = $this->decryptGCM($bufferObj->pack, $bufferObj->tag ?? '', $key);

        } else {
            $decrypted = $this->decrpyt($bufferObj->pack, $key);
        }
        $this->log('ReceiveData', 'Decrypted data: ' . $decrypted, GREELogType::INFO);
        $decObj = json_decode($decrypted);

        switch($actCmd){
            case GREECommands::scan:
                if (!is_object($decObj) || !property_exists($decObj, 'mac') || $decObj->mac === null) {
                    $this->log('ReceiveData', 'Fehler: Feld "mac" fehlt oder ist null! Decrypted: ' . print_r($decrypted, true), GREELogType::ERROR);
                    return;
                }
                $mac = strtoupper(implode(':', str_split($decObj->mac, 2)));
                SetValueString($this->GetIDForIdent('macAddress'), $mac);
                SetValueString($this->GetIDForIdent('name'), isset($decObj->name) ? $decObj->name : '<unknown>');
                SetValueString($this->GetIDForIdent('lastUpdate'), 'init '.date("Y-m-d H:i:s"));
                $this->reduceCmdQueue();
                $this->deviceBind();
                break;
            case GREECommands::bind:
                SetValueString($this->GetIDForIdent('deviceKey'), $decObj->key);

                SetValueString($this->GetIDForIdent('lastUpdate'), 'bind '.date("Y-m-d H:i:s"));

                $this->reduceCmdQueue();
                break;
            case GREECommands::status:
                $this->parseStatus($decObj->cols, $decObj->dat);

                SetValueString($this->GetIDForIdent('lastUpdate'), date("Y-m-d H:i:s"));

                $this->reduceCmdQueue();
                break;
            case GREECommands::cmd:
                $this->parseStatus($decObj->opt, $decObj->p);

                $this->reduceCmdQueue();
                break;
        }
    }

    private function sendCommand($type, $cmdArr){
        $this->log('sendCommand', 'send command: '.$type.' with data: '.json_encode($cmdArr), GREELogType::INFO);
        $cmdQueue = $this->getCmdQueue();
        $bAddCmd = true;

        // add the status command only once in queue
        if($type == GREECommands::status){
            foreach($cmdQueue as $cmd){
                if($cmd['TYPE'] == GREECommands::status){
                    $bAddCmd = false;
                    $this->log('sendCommand', 'status command already in queue', GREELogType::INFO);
                    break;
                }
            }
        }
        if(count($cmdQueue) > 5) {
            $this->log('sendCommand', 'more then 5 commands in queue', GREELogType::INFO);
            $bAddCmd = false;
        }

        // empty queue if init or bind commands are sent
        if($type == GREECommands::scan || $type == GREECommands::bind){
            $this->log('sendCommand', 'sending '.($type == GREECommands::scan ? 'init' : 'bind').' command -> empty queue', GREELogType::INFO);
            $this->resetCmd();
            $cmdQueue = array();
            $bAddCmd = true;
        }

        if($bAddCmd) {
            $cmdQueue[] = array('TYPE' => $type, 'CMD' => $cmdArr, 'TIMESTAMP' => 0);
            $this->setCmdQueue($cmdQueue);

            $this->cmdQueueWorker();
        }

        return true;
    }

    public function cmdQueueWorker(){
        $cmdQueue = $this->getCmdQueue();

        if($cmdQueue == '' || count($cmdQueue) == 0){
            // queue is empty -> disable timer
            $this->SetTimerInterval('queue_WorkerTimer', 0);
            return;
        } else{
            $this->SetTimerInterval('queue_WorkerTimer', 1000);
        }

        // while waiting for response send no other command
        if($this->GetBuffer('actualCommand') != GREECommands::none){
            $cmdWaitingTimeMilliSecs = (microtime(true) - $cmdQueue[0]['TIMESTAMP'])*1000;

            if($cmdWaitingTimeMilliSecs >= 10000) {
                $this->resetCmd();
                $this->SetTimerInterval('queue_WorkerTimer', 60000);
                return;
            }else {
                return;
            }
        }

        $type = $cmdQueue[0]['TYPE'];
        $cmdArr = $cmdQueue[0]['CMD'];

        try {
            $this->SetBuffer('actualCommand', $type);
            $cmdQueue[0]['TIMESTAMP'] = microtime(true);
            $this->setCmdQueue($cmdQueue);
            if ($type == GREECommands::scan) {
                // Scan/initDevice: Sende per Broadcast und leite Antwort an ReceiveData
                $json = json_encode($cmdArr);
                $broadcastIp = $this->ReadPropertyString("broadcastAddress");
                $response = $this->sendBroadcast($json, $broadcastIp, 7000);
                if ($response !== false) {
                    $this->log('cmdQueueWorker', 'Broadcast-Response: ' . $response, GREELogType::INFO);
                    $this->ReceiveData(json_encode([
                        "Buffer" => $response
                    ]));
                } else {
                    $this->log('cmdQueueWorker', 'Keine Antwort auf Broadcast erhalten.', GREELogType::ERROR);
                }
            } else {
                $this->SendDataToParent(json_encode(Array("DataID" => "{79827379-F36E-4ADA-8A95-5F8D1DC92FA9}", "Buffer" => json_encode($cmdArr))));
                $this->log('QueueWorker', 'send command: '.$type.' with data: '.json_encode($cmdArr), GREELogType::INFO);
            }
        }catch (Exception $e){
            $this->log('QueueWorker', $e->getMessage(), GREELogType::ERROR);

            // set to none command to prevent blocking
            $this->resetCmd();
        }
    }

    private function reduceCmdQueue(){
        $cmdQueue = $this->getCmdQueue();
        array_splice($cmdQueue, 0, 1);
        $this->setCmdQueue($cmdQueue);

        $this->resetCmd();
    }
    private function getCmdQueue(){
        $cmdQueue = $this->GetBuffer('cmdQueue');
        if($cmdQueue == '')
            $cmdQueue = array();
        else
            $cmdQueue = unserialize($cmdQueue);

        return $cmdQueue;
    }
    private function setCmdQueue($queue){
        $this->SetBuffer('cmdQueue', serialize($queue));
    }
    private function resetCmd(){
        $this->SetBuffer('actualCommand', GREECommands::none);
    }
    public function resetCmdQueue(){
        $cmdQueue = array();
        $this->setCmdQueue($cmdQueue);
        $this->resetCmd();
    }

    public function initDevice(){
        $arr = array('t' => 'scan');
        $this->sendCommand(GREECommands::scan, $arr);
    }

    private function deviceBind(){
        $pack = array(
            't' => 'bind',
            'uid' => 0,
            'mac' => $this->getMacUnformatted()
        );
        $this->sendCommand(GREECommands::bind, $this->getRequest($pack, true));
    }
    public function getStatus(){
        // if no device key or last change is older then 15 minutes -> init
        $varInfo = IPS_GetVariable ($this->GetIDForIdent('lastUpdate'));
        $lastStatusUpdateAgeSec = (time() - $varInfo['VariableChanged']);
        $maxAge = $this->ReadPropertyInteger('statusTimer') * 15;
        if(empty(GetValueString($this->GetIDForIdent('deviceKey')))
            || ($maxAge > 0 && $lastStatusUpdateAgeSec > $maxAge)){
            $this->log('getStatus', 'device key is empty or last update is more than '.$maxAge.' seconds ago -> init device', GREELogType::INFO);
            $this->initDevice();
            return;
        }

        $cols = array();
        $cols[] = GREEDeviceParam::Power;
        $cols[] = GREEDeviceParam::Mode;
        $cols[] = GREEDeviceParam::SetTemperature;
        $cols[] = GREEDeviceParam::ActTemperature;
        $cols[] = GREEDeviceParam::Fanspeed;
        $cols[] = GREEDeviceParam::Swinger;
        if($this->ReadPropertyBoolean("swingLeRi"))
            $cols[] = GREEDeviceParam::SwingerLeRi;
        if($this->ReadPropertyBoolean("freshAir"))
            $cols[] = GREEDeviceParam::OptAir;
        $cols[] = GREEDeviceParam::OptXFan;
        $cols[] = GREEDeviceParam::OptEco;
        $cols[] = GREEDeviceParam::OptHealth;
        $cols[] = GREEDeviceParam::OptLight;
        $cols[] = GREEDeviceParam::OptSleep1;

        $pack = array(
            't' => 'status',
            'mac' => $this->getMacUnformatted(),
            'cols' => $cols
        );
        $this->sendCommand(GREECommands::status, $this->getRequest($pack, false));
    }

    public function setPower(bool $newVal){
        $opts = array();
        $vals = array();

        $newOptLight = GetValueBoolean($this->GetIDForIdent('optLight'));
        if($this->ReadPropertyBoolean("autoLight"))
            $newOptLight = $newVal;

        $opts[] = GREEDeviceParam::Power;
        $opts[] = GREEDeviceParam::OptSleep1;
        $opts[] = GREEDeviceParam::OptSleep2;
        $opts[] = GREEDeviceParam::Mode;
        $opts[] = GREEDeviceParam::Fanspeed;
        $opts[] = GREEDeviceParam::OptQuiet;
        $opts[] = GREEDeviceParam::OptTurbo;
        $opts[] = GREEDeviceParam::Swinger;
        $opts[] = GREEDeviceParam::SetTemperature;
        $opts[] = GREEDeviceParam::OptXFan;
        $opts[] = GREEDeviceParam::OptHealth;
        $opts[] = GREEDeviceParam::OptLight;
        $opts[] = GREEDeviceParam::OptEco;

        $vals[] = $newVal ? 1 : 0;
        $vals[] = 0;
        $vals[] = 0;
        $vals[] = GetValueInteger($this->GetIDForIdent('mode'));
        $vals[] = GetValueInteger($this->GetIDForIdent('fan'));
        $vals[] = 0;
        $vals[] = 0;
        $vals[] = GetValueInteger($this->GetIDForIdent('swinger'));
        $vals[] = GetValueInteger($this->GetIDForIdent('setTemp'));
        $vals[] = GetValueBoolean($this->GetIDForIdent('optXFan')) ? 1 : 0;
        $vals[] = GetValueBoolean($this->GetIDForIdent('optHealth')) ? 1 : 0;
        $vals[] = $newOptLight ? 1 : 0;
        $vals[] = GetValueBoolean($this->GetIDForIdent('optEco')) ? 1 : 0;

        if($this->ReadPropertyBoolean("swingLeRi")) {
            $opts[] = GREEDeviceParam::SwingerLeRi;
            $vals[] = GetValueInteger($this->GetIDForIdent('swingerLeRi'));
        }
        if($this->ReadPropertyBoolean("freshAir")) {
            $opts[] = GREEDeviceParam::OptAir;
            $vals[] = GetValueInteger($this->GetIDForIdent('optAir'));
        }

        $cmd = $this->getCommand($opts, $vals);
        $this->sendCommand(GREECommands::cmd, $this->getRequest($cmd, false));
    }
    public function setMode(int $newVal){
        $cmd = $this->getCommand(array(GREEDeviceParam::Mode), array($newVal));
        $this->sendCommand(GREECommands::cmd, $this->getRequest($cmd, false));
    }
    public function setFan(int $newVal){
        if($newVal < 0 || $newVal > $this->ReadPropertyInteger("fanSteps"))
            return;

        $cmd = $this->getCommand(array(GREEDeviceParam::Fanspeed, "Quiet", "Tur"), array($newVal, 0, 0));
        $this->sendCommand(GREECommands::cmd, $this->getRequest($cmd, false));
    }
    public function setSwinger(int $newVal){
        if($newVal < 0 || $newVal > 11)
            return;

        $cmd = $this->getCommand(array(GREEDeviceParam::Swinger), array($newVal));
        $this->sendCommand(GREECommands::cmd, $this->getRequest($cmd, false));
    }
    public function setSwingerLeRi(int $newVal){
        if($newVal < 0 || $newVal > 6)
            return;

        $cmd = $this->getCommand(array(GREEDeviceParam::SwingerLeRi), array($newVal));
        $this->sendCommand(GREECommands::cmd, $this->getRequest($cmd, false));
    }
    public function setTemp(int $newVal){
        $cmd = $this->getCommand(array(GREEDeviceParam::SetTemperature), array($newVal));
        $this->sendCommand(GREECommands::cmd, $this->getRequest($cmd, false));
    }
    public function setOptXFan(bool $newVal){
        $cmd = $this->getCommand(array(GREEDeviceParam::OptXFan), array($newVal ? 1 : 0));
        $this->sendCommand(GREECommands::cmd, $this->getRequest($cmd, false));
    }
    public function setOptHealth(bool $newVal){
        $cmd = $this->getCommand(array(GREEDeviceParam::OptHealth), array($newVal ? 1 : 0));
        $this->sendCommand(GREECommands::cmd, $this->getRequest($cmd, false));
    }
    public function setOptLight(bool $newVal){
        $cmd = $this->getCommand(array(GREEDeviceParam::OptLight), array($newVal ? 1 : 0));
        $this->sendCommand(GREECommands::cmd, $this->getRequest($cmd, false));
    }
    public function setOptSleep(bool $newVal){
        $cmd = $this->getCommand(array(GREEDeviceParam::OptSleep1, GREEDeviceParam::OptSleep2), array($newVal ? 1 : 0, $newVal ? 1 : 0));
        $this->sendCommand(GREECommands::cmd, $this->getRequest($cmd, false));
    }
    public function setOptEco(bool $newVal){
        $cmd = $this->getCommand(array(GREEDeviceParam::OptEco), array($newVal ? 1 : 0));
        $this->sendCommand(GREECommands::cmd, $this->getRequest($cmd, false));
    }
    public function setOptAir(bool $newVal){
        $cmd = $this->getCommand(array(GREEDeviceParam::OptAir), array($newVal ? 1 : 0));
        $this->sendCommand(GREECommands::cmd, $this->getRequest($cmd, false));
    }

    public function beep(){
        $this->setOptLight(GetValueBoolean($this->GetIDForIdent('optHealth')));
    }

    private function parseStatus($cols, $dats){
        for($i=0;$i<count($cols);$i++){
            switch($cols[$i]){
                case GREEDeviceParam::Power:
                    SetValueBoolean($this->GetIDForIdent('power'), $dats[$i]!=0 ? true : false);
                    break;
                case GREEDeviceParam::Mode:
                    SetValueInteger($this->GetIDForIdent('mode'), $dats[$i]);
                    break;
                case GREEDeviceParam::Fanspeed:
                    SetValueInteger($this->GetIDForIdent('fan'), $dats[$i]);
                    break;
                case GREEDeviceParam::Swinger:
                    SetValueInteger($this->GetIDForIdent('swinger'), $dats[$i]);
                    break;
                case GREEDeviceParam::SwingerLeRi:
                    if($this->ReadPropertyBoolean("swingLeRi"))
                        SetValueInteger($this->GetIDForIdent('swingerLeRi'), $dats[$i]);
                    break;
                case GREEDeviceParam::SetTemperature:
                    SetValueInteger($this->GetIDForIdent('setTemp'), $dats[$i]);
                    break;
                case GREEDeviceParam::ActTemperature:
                    SetValueInteger($this->GetIDForIdent('actTemp'), $dats[$i]-40);
                    break;
                case GREEDeviceParam::OptXFan:
                    SetValueBoolean($this->GetIDForIdent('optXFan'), $dats[$i]!=0 ? true : false);
                    break;
                case GREEDeviceParam::OptHealth:
                    SetValueBoolean($this->GetIDForIdent('optHealth'), $dats[$i]!=0 ? true : false);
                    break;
                case GREEDeviceParam::OptLight:
                    SetValueBoolean($this->GetIDForIdent('optLight'), $dats[$i]!=0 ? true : false);
                    break;
                case GREEDeviceParam::OptSleep1:
                case GREEDeviceParam::OptSleep2:
                    SetValueBoolean($this->GetIDForIdent('optSleep'), $dats[$i]!=0 ? true : false);
                    break;
                case GREEDeviceParam::OptEco:
                    SetValueBoolean($this->GetIDForIdent('optEco'), $dats[$i]!=0 ? true : false);
                    break;
                case GREEDeviceParam::OptAir:
                    if($this->ReadPropertyBoolean("freshAir"))
                        SetValueBoolean($this->GetIDForIdent('optAir'), $dats[$i]!=0 ? true : false);
                    break;
            }
        }
    }

    private function getRequest($pack, $bDefKey=true){
        $encryptionType = $this->ReadPropertyString('encryptionType');
        if (isset($pack['t']) && $pack['t'] === 'bind') {
            $encryptionType = 'GCM';
        }
        if ($bDefKey) {
            $key = ($encryptionType === 'GCM') ? self::defaultGcmKey : self::defaultCryptKey;
        } else {
            $key = GetValueString($this->GetIDForIdent('deviceKey'));
        }
        $strPack = json_encode($pack);
        $this->log('getRequest', 'Using encryption type: ' . $encryptionType . ' - Pack: ' . $strPack, GREELogType::INFO);
        if ($encryptionType === 'GCM') {
            $enc = $this->encryptGCM($strPack, $key);
            $arr = array(
                'cid' => 'app',
                'i' => $bDefKey ? 1 : 0,
                'pack' => $enc['pack'],
                'tag' => $enc['tag'],
                't' => 'pack',
                'tcid' => $this->getMacUnformatted(),
                'uid' => 0
            );
        } else {
            $arr = array(
                'cid' => 'app',
                'i' => $bDefKey ? 1 : 0,
                'pack' => $this->encrypt($strPack, $key),
                't' => 'pack',
                'tcid' => $this->getMacUnformatted(),
                'uid' => 0
            );
        }
        return $arr;
    }

    private function getCommand($opts, $vals){
        $cmd = array(
            't' => 'cmd',
            'opt' => $opts,
            'p' => $vals
        );

        return $cmd;
    }

    private function getMacUnformatted(){
        $mac = GetValueString($this->GetIDForIdent('macAddress'));
        $mac = strtolower(str_replace(':', '', $mac));

        return $mac;
    }

    private function decrpyt( $message, $key ){
        if($key == '')
            $key = self::defaultCryptKey;

        $decrypt = openssl_decrypt(
            base64_decode( $message ),
            "aes-128-ecb",
            $key,
            OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING
        );

        // remove zero padding
        $decrypt = rtrim( $decrypt, "\x00" );
        // remove PKCS #7 padding
        $decrypt_len = strlen( $decrypt );
        $decrypt_padchar = ord( $decrypt[ $decrypt_len - 1 ] );
        for ( $i = 0; $i < $decrypt_padchar ; $i++ )
        {
            if ( $decrypt_padchar != ord( $decrypt[$decrypt_len - $i - 1] ) )
                break;
        }
        if ( $i != $decrypt_padchar )
            return $decrypt;
        else
            return substr(
                $decrypt,
                0,
                $decrypt_len - $decrypt_padchar
            );
    }

    private function encrypt( $message, $key ){
        if($key == '')
            $key = self::defaultCryptKey;

        $blocksize = 16;
        $encrypt_padchar = $blocksize - ( strlen( $message ) % $blocksize );
        $message .= str_repeat( chr( $encrypt_padchar ), $encrypt_padchar );

        return base64_encode(
            openssl_encrypt(
                $message,
                "aes-128-ecb",
                $key,
                OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING
            )
        );
    }

    private function encryptGCM($message, $key) {
        if ($key == '')
            $key = self::defaultGcmKey;
        $iv = hex2bin(self::GCM_IV);
        $aad = self::GCM_ADD;
        $cipher = openssl_encrypt($message, 'aes-128-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag, $aad);
        return array(
            'pack' => base64_encode($cipher),
            'tag' => base64_encode($tag)
        );
    }

    private function decryptGCM($pack_encoded, $tag_encoded, $key) {
        if ($key == '')
            $key = self::defaultGcmKey;
        $iv = hex2bin(self::GCM_IV);
        $aad = self::GCM_ADD;
        $ciphertext = base64_decode($pack_encoded);
        $tag = base64_decode($tag_encoded);
        $decrypted = openssl_decrypt($ciphertext, 'aes-128-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag, $aad);
        $decrypted = str_replace("\xFF", '', $decrypted);
        return $decrypted;
    }

    private function sendBroadcast($data, $broadcastIp, $port = 7000) {
        $sock = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
        if ($sock === false) {
            $this->log('sendBroadcast', 'Socket konnte nicht erstellt werden: ' . socket_strerror(socket_last_error()), GREELogType::ERROR);
            return false;
        }
        socket_set_option($sock, SOL_SOCKET, SO_BROADCAST, 1);
        socket_sendto($sock, $data, strlen($data), 0, $broadcastIp, $port);
        socket_set_option($sock, SOL_SOCKET, SO_RCVTIMEO, ["sec"=>2,"usec"=>0]);
        $buf = '';
        $from = '';
        $fromPort = 0;
        $bytes = @socket_recvfrom($sock, $buf, 1024, 0, $from, $fromPort);
        socket_close($sock);
        return $bytes !== false ? $buf : false;
    }

    private function log($name, $data, $logLevel){
        $bLog = true;

        if($logLevel == GREELogType::INFO && !$this->ReadPropertyBoolean("logInfo"))
            $bLog = false;

        if($bLog)
            IPS_LogMessage('Sinclair '.$name, IPS_GetProperty($this->GetParent(), 'Host').': '.$data);
    }

    //------------------------------------------------------------------------------
    /**
     * Check if a parent for Instance $id exists
     * @param $id integer InstanceID
     * @return integer
     */
    protected function GetParent($id = 0)
    {
        $parent = 0;
        if ($id == 0) $id = $this->InstanceID;
        if (IPS_InstanceExists($id)) {
            $instance = IPS_GetInstance($id);
            $parent = $instance['ConnectionID'];
        } else {
            $this->log('GetParent', "Instance #$id doesn't exists", GREELogType::ERROR);
        }
        return $parent;
    }
//------------------------------------------------------------------------------
    /**
     * Retrieve instance status
     * @param int $id
     * @return mixed
     */
    protected function GetInstanceStatus($id = 0)
    {
        if ($id == 0) $id = $this->InstanceID;
        $inst = IPS_GetInstance($id);
        return $inst['InstanceStatus'];
    }
}
?>