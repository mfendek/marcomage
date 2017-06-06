# MArcomage

## Introduction

MArcomage (Multiplayer Arcomage) is an online version of the classic fantasy turn-based card game Arcomage, which appears as a minigame in the Might and Magic series of PC games. This card game simulates an epic battle between two empires including features such as constructing new buildings, training troops and decimating the enemy empire with standard attacks or sneaky tactics such as intrigue or spells.

The project is a PHP web-based application which features a deck builder, player challenging system, various means of communication, and most importantly the actual duel engine. Players may customize their profile and adjust the interface's appearance. The gameplay itself is turn-based, but contrary to the original it doesn't have to be played in real time.

## Installation

1. checkout SVN repository from `https://arcomage.net/svn/`
2. run composer install in `release` directory
3. run DB install script located in `release/scripts/install_tables.sql`

## Configuration

Base config file is located in `release/src/config.php` and is kept under version control. Production specific config is located in `release/src/config_production.php`. This file however is not part of the codebase. Base config file is merged with production config with the latter overriding the former. Configuration that is not present in the production config is inherited from base config file. 

