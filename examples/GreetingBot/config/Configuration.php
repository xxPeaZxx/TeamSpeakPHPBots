<?php
/**
 * Copyright (c) 2016 by Botorabi. All rights reserved.
 * https://github.com/botorabi/TeamSpeakPHPBots
 * 
 * License: MIT License (MIT), read the LICENSE text in
 *          main directory for more details.
 */

/**
 * Main framework and application configuration
 * 
 * NOTE: $TSPHPBOT_CONFIG_WEB_INTERFACE and $TSPHPBOT_CONFIG_BOT_SERVICE have own
 *       semantically similar variables: appSrc and libSrc. The reason is that
 *       the bot server may run from another location than the web service. So you can
 *       use different relative paths for each part.
 * 
 * @created:  2nd August 2016
 * @author:   Botorabi
 */
abstract class Configuration {

    //! TS3 Server Query related config
    public static  $TSPHPBOT_CONFIG_TS3SERVER_QUERY = [
        "host"          => "localhost",
        "userName"      => "serveradmin",       // TODO put your TS3 user name here
        "password"      => "exffRAo3",          // TODO put your TS3 user password here
        "hostPort"      => 10011,
        "vServerPort"   => 9987,
        "pollInterval"  => 2,                   // intervall of bot control steps in seconds
        "nickName"      => "TS3 PHP Bot"        // this is the name displayed in TS3 clients
    ];

    //! Databank account info (MySQL)
    public static $TSPHPBOT_CONFIG_DB = [
        "host"          => "localhost",
        "hostPort"      => 3306,
        "dbName"        => "tsphpbots",
        "userName"      => "tsphpbotserver",
        "password"      => "tsphpbotserver",
        "tablePrefix"   => "tsphpbots_"
    ];

    //! Web interface related info
    public static $TSPHPBOT_CONFIG_WEB_INTERFACE = [
        // Web service version.
        "version"        => "0.1.0",
        // Timeout for automatic logout while inactive (in minutes)
        "sessionTimeout" => 30,
        // App's main source directory. This path is relative to executing path (you should see the directory structure 'com/examples/...' there).
        "appSrc"         => "./src",
        // TeamSpeakPHPBots library's main source directory. You may have put it to /usr/local/share/TeamSpeakPHPBots, though. Who knows?
        "libSrc"         => "../../libraries/TeamSpeakPHPBots/src",
        // All web related assets are relative to this path
        "dirBase"        => "src",
        "dirTemplates"   => "web/templates",
        "dirJs"          => "web/js",
        "dirStyles"      => "web/styles",
        "dirImages"      => "web/images",
        "dirLibs"        => "web/libs"
    ];

    //! Bot service related config
    public static  $TSPHPBOT_CONFIG_BOT_SERVICE = [
        // Bot serivce version
        "version"       => "0.1.0",
        // Bot service query IP
        "host"          => "127.0.0.1",
        // Bot service query port
        "hostPort"      => 12000,
        // Command line for starting the bot service, used by web service
        "cmdStart"      => "cd src; php botserver.php > botserver.log  2>&1 & echo $! > botserver.pid",
        // App's main source directory. This path is relative to executing path (you should see the directory structure 'com/examples/...' there).
        "appSrc"         => "./",
        // TeamSpeakPHPBots library's main source directory. You may have put it to /usr/local/share/TeamSpeakPHPBots, though. Who knows?
        "libSrc"         => "../../../libraries/TeamSpeakPHPBots/src"
    ];
}
