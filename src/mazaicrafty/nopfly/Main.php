<?php

/*
 * ___  ___               _ _____            __ _         
 * |  \/  |              (_)  __ \          / _| |        
 * | .  . | __ _ ______ _ _| /  \/_ __ __ _| |_| |_ _   _ 
 * | |\/| |/ _` |_  / _` | | |   | '__/ _` |  _| __| | | |
 * | |  | | (_| |/ / (_| | | \__/\ | | (_| | | | |_| |_| |
 * \_|  |_/\__,_/___\__,_|_|\____/_|  \__,_|_|  \__|\__, |
 *                                                   __/ |
 *                                                  |___/
 * Copyright (C) 2017-2018 @MazaiCrafty (https://twitter.com/MazaiCrafty)
 *
 * This program is free plugin.
 */

namespace mazaicrafty\nopfly;

use pocketmine\plugin\PluginBase;
use pocketmine\Server;
use pocketmine\Player;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerToggleFlightEvent;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\CommandExecuter;
use pocketmine\command\ConsoleCommandSender;

use pocketmine\utils\TextFormat as COLOR;
use pocketmine\utils\Config;

use jojoe77777\FormAPI\FormAPI;

class Main extends PluginBase implements Listener{

    const PREFIX = "§a[§dNoOP NoFly§a]§r ";
    const VERSION = "1.1.3";
    
    public function onEnable(): void{
        $this->allRegisterEvents();

        $this->config = new Config($this->getDataFolder() . "Config.yml", Config::YAML, array(
            "LANGUAGE" => "jpn",
            "FLYKICK" => true
            "SETTINGMETHOD" => "command",
            "PUNISHMENT" => "kick",
            "BROADCAST" => "§a%PLAYER%§bから不正なFlyを検知したため、キックを実行しました。",
            "KICKMESSAGE" => "§a不正なFlyを検知したのでキックを実行しました",
            "BANMESSAGE" => "§a不正なFlyを検知したのでBANを実行しました"
        ));
        $this->checkAPI();
        $this->query();
    }

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool{
        
        if (!($sender->isOp())){
            $sender->sendMessage(self::PREFIX . COLOR::RED . "You don't have permission to execute this command.");
            return true;
        }

        if (!($sender instanceof Player)){
            $sender->sendMessage("Please execute this command in-game");
            return true;
        }

        switch ($command->getName()){
            case 'nopfly':
            $setting = $this->config->get("SETTINGMETHOD");
            if ($setting === "form"){
                $this->createMainForm($sender);
                return true;
            }

            if (!(isset($args[0]))) return false;

            switch ($args[0]){
                case 'on':
                case 'true':
                $this->config->set("FLYKICK", true);
                $this->config->save();
                $sender->sendMessage(self::PREFIX . COLOR::YELLOW . "フライキックを" . COLOR::GREEN . "有効" . COLOR::YELLOW . "に設定しました");
                return true;

                case 'off':
                case 'false':
                $this->config->set("FLYKICK", false);
                $this->config->save();
                $sender->sendMessage(self::PREFIX . COLOR::YELLOW . "フライキックを" . COLOR::RED . "無効" . COLOR::YELLOW . "に設定しました");
                return true;

                case 'punishment':
                case 'batu':
                if (!(isset($args[1]))) return false;

                switch ($args[1]){
                    case 'ban':
                    $this->config->set("PUNISHMENT", "ban");
                    $this->config->save();

                    if ($this->config->get("LANGUAGE") === "jpn"){
                        $sender->sendMessage(self::PREFIX . COLOR::YELLOW . "設定を変更しました " . COLOR::BLUE . ":\n" . COLOR::WHITE . "ターゲットの処罰方法は" . COLOR::RED . "[BAN]");
                        return true;        
                    }
                    else{
                        $sender->sendMessage(self::PREFIX . COLOR::YELLOW . "The setting was changed " . COLOR::BLUE . ":\n" . COLOR::WHITE . "ターゲットの処罰方法は" . COLOR::RED . "[BAN]");
                        return true;
                    }

                    case 'kick':
                    $this->config->set("PUNISHMENT", "kick");
                    $this->config->save();

                    if ($this->config->get("LANGUAGE") === "jpn"){
                        $sender->sendMessage(self::PREFIX . COLOR::YELLOW . "設定を変更しました " . COLOR::BLUE . ":\n" . COLOR::WHITE . "ターゲットの処罰方法は " . COLOR::RED . "[KICK]");
                        return true;
                    }
                    else{
                        $sender->sendMessage(self::PREFIX . COLOR::YELLOW . "The setting was changed " . COLOR::BLUE . ":\n" . COLOR::WHITE . "The way to punish the target " . COLOR::RED . "[KICK]");
                        return true;
                    }
                }

                case 'lang':
                if (!(isset($args[1]))) return false;

                switch ($args[1])){
                    case 'jpn':
                    $this->config->set("LANGUAGE", "jpn");
                    $this->config->save();
                    $sender->sendMessage(self::PREFIX . COLOR::YELLOW . "設定を変更しました " . COLOR::BLUE . ":\n" . COLOR::WHITE . "言語設定 " . COLOR::GREEN . "[日本語]");
                    return true;

                    default:
                    $this->config->set("LANGUAGE", "eng");
                    $this->config->save();
                    $sender->sendMessage(self::PREFIX . COLOR::YELLOW . "The setting was changed " . COLOR::BLUE . ":\n" . COLOR::WHITE . "Language setting " . COLOR::GREEN . "[English]");
                    return true;
                }

                case 'setmethod':
                if (!(isset($args[1]))) return false;

                switch ($args[1]){
                    case 'form':
                    $this->config->set("SETTINGMETHOD", "form");
                    $this->config->save();

                    if ($this->config->get("LANGUAGE") === "jpn"){
                        $sender->sendMessage(self::PREFIX . COLOR::YELLOW . "設定を変更しました " . COLOR::BLUE . ":\n" . COLOR::WHITE . "操作方法 " . COLOR::GREEN . "Form");
                            eturn true;
                    }
                    else{
                        $sender->sendMessage(self::PREFIX . COLOR::YELLOW . "The setting was changed " . COLOR::BLUE . ":\n" . COLOR::WHITE . "Method of operation " . COLOR::GREEN . "Form");
                        return true;
                    }
                }

                case 'query':
                case 'config':
                $enable = $this->config->get("LANGUAGE") === "jpn" ? "[有効]\n" : "[Enable]\n";
                $disable = $this->config->get("LANGUAGE") === "jpn" ? "[無効]\n" : "[Disable]\n";

                $flykick = $this->config->get("FLYKICK") ? COLOR::GREEN . $enable : COLOR::RED . $disable;
                $punishment = $this->config->get("PUNISHMENT") === "kick" ? COLOR::YELLOW . "[KICK]" : COLOR::RED . "[BAN]";

                switch ($this->config->get("LANGUAGE") === "jpn"){
                    case "jpn":
                    $sender->sendMessage(self::PREFIX . COLOR::YELLOW . "現在の設定:\n" . COLOR::WHITE . "フライキック " . $flykick . COLOR::WHITE . "処罰方法" . $punishment);
                    return true;

                    case "eng":
                    $sender->sendMessage(self::PREFIX . COLOR::YELLOW . "Current settings:\n" . COLOR::WHITE . "Flykick " . $flykick . COLOR::WHITE . "The way to punish the target " . $punishment);
                    return true;
                }
            }
        }
        return false;
    }

    public function onSenseFly(PlayerToggleFlightEvent $event){
        $player = $event->getPlayer();
        $name = $player->getName();
        $mode = $event->isFlying();
        $players = Server::getInstance()->getOnlinePlayers();

        $str = $this->config->get("BROADCAST");
        $broadcast = str_replace("%PLAYER%", $name, $str);

        $banmessage = $this->config->get("BANMESSAGE");
        $kickmessgae = $this->config->get("KICKMESSAGE");

        $punishment = $this->config->get("PUNISHMENT");

        foreach ($players as $player){
            if (!$player->isOp()) {
                if ($mode){
                    Server::getInstance()->broadcastMessage(self::PREFIX . COLOR::WHITE . $broadcast);
                    switch ($punishment){
                        case "ban":
                        Server::getInstance()->getNameBans()->addBan($player, $banmessage, null, "NoPFly-Plugin");
                        break;

                        case "kick":
                        $player->kick($kickmessgae, false);
                        break;

                        default:
                        Server::getInstance()->dispatchCommand(new ConsoleCommandSender(), "${punishment} ".$name." ${banmessage}");
                        break;
                    }
                }else{
                    Server::getInstance()->broadcastMessage(self::PREFIX . COLOR::WHITE . $broadcast);
                    switch ($punishment){
                        case "ban":
                        Server::getInstance()->getNameBans()->addBan($player, $banmessage, null, "NoPFly-Plugin");
                        break;

                        case "kick":
                        $player->kick($kickmessgae, false);
                        break;

                        default:
                        Server::getInstance()->dispatchCommand(new ConsoleCommandSender(), "${punishment} ", $name, " ${banmessage}");
                        break;
                    }
                }
            }
        }
    }

    public function createMainForm($player){
        $api = $this->getServer()->getPluginManager()->getPlugin("FormAPI");
        $form = $api->createSimpleForm(function (Player $event, array $args){
            $result = $args[0];
            $player = $event->getPlayer();
            if ($result === null){
            }
            switch ($result){
                case 0:
                // メニューが閉じまーす
                return;
                case 1:
                $this->createConfSet($player);
                // createConfSet関数呼び出し
                return;
                case 2:
                $this->createPunishment($player);
                // createPunishment関数呼び出し
                return;
                case 3:
                return;
                // 今はこれでいいかな...増やしたかったら上のように。
            }
        });

        $lang = $this->config->get("LANGUAGE");
        if ($lang == "jpn"){
            // 日本語
            $form->setTitle("§e--= §l§5NoOP NoFLY§r§e =--");
            $form->setContent("§a選択してください。");
            $form->addButton("§l§cメニューを閉じる");
            $form->addButton("§d設定画面");
            $form->addButton("§d処罰方法");
            $form->addButton("§dプラグイン関連");
            $form->sendToPlayer($player);
        }else{
            // 英語
            $form->setTitle("§e--= §l§5NoOP NoFLY§r§e =--");
            $form->setContent("§aPlease select.");
            $form->addButton("§l§cClose Menu");
            $form->addButton("§dSetting");
            $form->addButton("§dPunishment Method");
            $form->addButton("§dPlugin related");
            $form->sendToPlayer($player);          
        }
    }

    public function createConfSet($player){
        $api = $this->getServer()->getPluginManager()->getPlugin("FormAPI");
        $form = $api->createSimpleForm(function (Player $event, array $data){
            $result = $data[0];
            $player = $event->getPlayer();
            if ($result === null){
            }
            switch ($result){
                case 0:
                $this->createMainForm($player);
                // 前に戻りま～す
                break;

                case 1:
                $lang = $this->config->get("LANGUAGE");
                switch ($lang){
                    case "jpn":
                    $this->config->set("LANGUAGE", "eng");
                    $this->config->save();
                    $player->sendMessage(self::PREFIX . COLOR::YELLOW . "The setting was changed " . COLOR::BLUE . ":\n" . COLOR::WHITE . "Language setting " . COLOR::GREEN . "[English]");
                    break;

                    default:
                    $this->config->set("LANGUAGE", "jpn");
                    $this->config->save();
                    $player->sendMessage(self::PREFIX . COLOR::YELLOW . "設定を変更しました " . COLOR::BLUE . ":\n" . COLOR::WHITE . "言語設定 " . COLOR::GREEN . "[日本語]");
                    break;
                }
                // 処理
                break;

                case 2:
                $this->config->set("SETTINGMETHOD", "command");
                $this->config->save();
                $lang = $this->config->get("LANGUAGE");

                switch ($lang){
                    case "jpn":
                    $player->sendMessage(self::PREFIX . COLOR::YELLOW . "設定を変更しました " . COLOR::BLUE . ":\n" . COLOR::WHITE . "設定方法 " . COLOR::GREEN . "Command");
                    break;

                    default:
                    $player->sendMessage(self::PREFIX . COLOR::YELLOW . "The setting was changed " . COLOR::BLUE . ":\n" . COLOR::WHITE . "Method of setting " . COLOR::GREEN . "Command");
                }
                // 処理
                break;
                // これも今はこんなんでええやろ 俺ら足りなくなったら足すだけやから。
            }
        });

        $lang = $this->config->get("LANGUAGE");
        if ($lang == "jpn"){
            // 日本語
            $form->setTitle("§e--= §l§5NoOP NoFLY§r§e =--");
            $form->setContent("§l§a設定画面");
            $form->addButton("§cメニューに戻る");
            $form->addButton("§l§b言語設定を§a[English]§bに変更");
            $form->addButton("§l§b設定方法を§aコマンド§bに変更");
            $form->sendToPlayer($player);
        }else{
            // 英語
            $form->setTitle("§e--= §l§5NoOP NoFLY§r§e =--");
            $form->setContent("§l§aSetting Menu");
            $form->addButton("§cBack Menu");
            $form->addButton("§l§bSet the language setting to §a[日本語]");
            $form->addButton("§l§bChange setting method to §aCommand");
            $form->sendToPlayer($player);
        }
    }

    public function createPunishment($player){
        $api = $this->getServer()->getPluginManager()->getPlugin("FormAPI");
        $form = $api->createSimpleForm(function (Player $event, array $data){
            $result = $data[0];
            $player = $event->getPlayer();
            if ($result === null){
            }
            switch ($result){
                case 0:
                $this->createMainForm();
                return;
                // いつもの

                case 1:
                $this->config->set("PUNISHMENT", "ban");
                $this->config->save();
                $lang = $this->config->get("LANGUAGE");

                if ($lang == "jpn"){
                    $player->sendMessage(self::PREFIX . COLOR::YELLOW . "設定を変更しました " . COLOR::BLUE . ":\n" . COLOR::WHITE . "ターゲットの処罰方法は " . COLOR::RED . "[BAN]");                
                }else{
                    $player->sendMessage(self::PREFIX . COLOR::YELLOW . "The setting was changed " . COLOR::BLUE . ":\n" . COLOR::WHITE . "The way to punish the target " . COLOR::RED . "[BAN]");
                }
                break;

                case 2:
                $this->config->set("PUNISHMENT", "kick");
                $this->config->save();
                $lang = $this->config->get("LANGUAGE");

                if ($lang == "jpn"){
                    $player->sendMessage(self::PREFIX . COLOR::YELLOW . "設定を変更しました " . COLOR::BLUE . ":\n" . COLOR::WHITE . "ターゲットの処罰方法は " . COLOR::RED . "[KICK]");                
                }else{
                    $player->sendMessage(self::PREFIX . COLOR::YELLOW . "The setting was changed " . COLOR::BLUE . ":\n" . COLOR::WHITE . "The way to punish the target " . COLOR::RED . "[KICK]");
                }
                break;
                // これも今はこれで
            }
        });

        $lang = $this->config->get("LANGUAGE");
        $punishment = $this->config->get("PUNISHMENT");
        if ($lang == "jpn"){
            // 日本語
            $form->setTitle("§e--= §l§5NoOP NoFLY§r§e =--");
            $form->setContent("§l§a現在の処罰方法は§o§c " . $punishment . " §r§l§aです");
            $form->addButton("§cメニューに戻る");
            $form->addButton("§l§b処罰方法を§aBan§bに変更");
            $form->addButton("§l§b処罰方法を§aKick§bに変更");
            $form->sendToPlayer($player);
        }else{
            // 英語
            $form->setTitle("§e--= §l§5NoOP NoFLY§r§e =--");
            $form->setContent("");
            $form->addButton("§cBack Menu");
            $form->addButton("§l§bChanged punishment method to §aBan");
            $form->addButton("§l§bChanged punishment method to §aKick");
            $form->sendToPlayer($player);          
        }
    }

    public function createPluginSet($player){
        
    }

    public function onDisable(): void{
        Server::getInstance()->getLogger()->info(self::PREFIX . COLOR::GRAY . "is Disabled...");
    }

    public function messageA(): void{
        Server::getInstance()->getLogger()->info(self::PREFIX . COLOR::YELLOW . "is Enabling!");
        Server::getInstance()->getLogger()->info(self::PREFIX . COLOR::AQUA . "Version " . COLOR::GREEN . self::VERSION);
        Server::getInstance()->getLogger()->info(self::PREFIX . COLOR::GRAY . "https://github.com/MazaiCrafty");
        Server::getInstance()->getLogger()->info(self::PREFIX . COLOR::WHITE . "Thank you for observing the specified license." . COLOR::BLUE . " by @MazaiCrafty");
    }

    public function error(): void{
        Server::getInstance()->getLogger()->warning(self::PREFIX . COLOR::YELLOW . "FormAPI is not found.");
        Server::getInstance()->getLogger()->critical(self::PREFIX . COLOR::WHITE . "Please install [FormAPI] plugin.");
        Server::getInstance()->getLogger()->info(self::PREFIX . COLOR::WHITE . "GtiHub: " . COLOR::GRAY . "https://github.com/jojoe77777/FormAPI");
        Server::getInstance()->shutdown();// Shutdown
    }

    public function query(){
        $query = $this->config->get("FLYKICK");
        if (!$query == "true"){
            Server::getInstance()->getLogger()->info(self::PREFIX . COLOR::YELLOW . "現在の設定: " . COLOR::RED . "無効");
        }else{
            Server::getInstance()->getLogger()->info(self::PREFIX . COLOR::YELLOW . "現在の設定: " . COLOR::GREEN . "有効");
        }
    }

    public function checkAPI(){
        // なぜかFormAPIの名前を取得できずにerror関数が呼び出されてshutdownが実行されてしまう なんでや しばらく保留しておこうか... by魔剤。
        $api = Server::getInstance()->getPluginManager()->getPlugin("FormAPI");
        $setmethod = $this->config->get("SETTINGMETHOD");
        if ($setmethod == "form"){
            if ($api === null){
                $this->error();
            }else{
                $this->messageA();
            }
        }else{
            $this->messageA();
        }
    }

    public function allRegisterEvents(){
        if(!file_exists($this->getDataFolder())){
            mkdir($this->getDataFolder(), 0755, true); 
            }
        Server::getInstance()->getPluginManager()->registerEvents($this, $this);
    }
}
