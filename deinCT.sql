-- phpMyAdmin SQL Dump
-- version 4.6.0
-- http://www.phpmyadmin.net
--
-- Host: localhost:3306
-- Erstellungszeit: 18. Jun 2016 um 01:24
-- Server-Version: 5.5.46-0ubuntu0.14.04.2
-- PHP-Version: 5.6.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Datenbank: `mycts`
--

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `ctguests`
--

CREATE TABLE `ctguests` (
  `id` int(10) NOT NULL,
  `ctid` int(6) NOT NULL,
  `tw_id` varchar(32) NOT NULL,
  `coming` int(11) NOT NULL,
  `privacy` int(1) NOT NULL DEFAULT '1'
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `ctphotos`
--

CREATE TABLE `ctphotos` (
  `id` int(10) UNSIGNED NOT NULL,
  `picid` varchar(35) NOT NULL,
  `ctid` int(10) UNSIGNED NOT NULL,
  `uploader` varchar(32) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `views` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `uploadtime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `cts`
--

CREATE TABLE `cts` (
  `id` int(6) NOT NULL,
  `name` varchar(60) NOT NULL,
  `creator` varchar(32) NOT NULL,
  `description` text NOT NULL,
  `time` int(15) NOT NULL,
  `place` varchar(120) NOT NULL,
  `public` tinyint(1) NOT NULL,
  `picenabled` tinyint(1) NOT NULL DEFAULT '1',
  `archived` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `ctstats`
--

CREATE TABLE `ctstats` (
  `ctid` int(11) NOT NULL,
  `current` int(11) NOT NULL,
  `last` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `log`
--

CREATE TABLE `log` (
  `id` int(10) NOT NULL,
  `type` varchar(20) NOT NULL,
  `user` varchar(32) NOT NULL DEFAULT 'none',
  `message` varchar(255) NOT NULL,
  `time` int(10) NOT NULL,
  `ip` varchar(30) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `news`
--

CREATE TABLE `news` (
  `id` int(11) NOT NULL,
  `title` varchar(120) NOT NULL,
  `text` text NOT NULL,
  `time` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `picreports`
--

CREATE TABLE `picreports` (
  `id` int(10) UNSIGNED NOT NULL,
  `sender` varchar(32) NOT NULL,
  `ctid` int(11) NOT NULL,
  `picid` int(11) NOT NULL,
  `time` int(10) UNSIGNED NOT NULL,
  `reason` varchar(255) NOT NULL,
  `message` varchar(5000) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `stats`
--

CREATE TABLE `stats` (
  `type` varchar(25) NOT NULL,
  `current` int(11) NOT NULL,
  `last` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `twtokens`
--

CREATE TABLE `twtokens` (
  `user` varchar(32) NOT NULL,
  `token` varchar(255) NOT NULL,
  `secret` varchar(255) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `users`
--

CREATE TABLE `users` (
  `id` int(6) NOT NULL,
  `tw_uid` varchar(32) NOT NULL,
  `tw_name` varchar(30) NOT NULL,
  `tw_pic` varchar(240) NOT NULL,
  `tw_bio` varchar(300) NOT NULL,
  `email` varchar(255) NOT NULL,
  `registered` datetime NOT NULL,
  `last_login` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `approved` int(1) NOT NULL DEFAULT '0',
  `banned` int(1) NOT NULL DEFAULT '0',
  `admin` int(1) NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Indizes der exportierten Tabellen
--

--
-- Indizes für die Tabelle `ctguests`
--
ALTER TABLE `ctguests`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `id` (`id`);

--
-- Indizes für die Tabelle `ctphotos`
--
ALTER TABLE `ctphotos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `id` (`id`);

--
-- Indizes für die Tabelle `cts`
--
ALTER TABLE `cts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `id` (`id`);

--
-- Indizes für die Tabelle `ctstats`
--
ALTER TABLE `ctstats`
  ADD PRIMARY KEY (`ctid`),
  ADD UNIQUE KEY `ctid` (`ctid`);

--
-- Indizes für die Tabelle `log`
--
ALTER TABLE `log`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `id` (`id`);

--
-- Indizes für die Tabelle `news`
--
ALTER TABLE `news`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `id` (`id`);

--
-- Indizes für die Tabelle `picreports`
--
ALTER TABLE `picreports`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `id` (`id`);

--
-- Indizes für die Tabelle `stats`
--
ALTER TABLE `stats`
  ADD PRIMARY KEY (`type`),
  ADD UNIQUE KEY `type` (`type`);

--
-- Indizes für die Tabelle `twtokens`
--
ALTER TABLE `twtokens`
  ADD UNIQUE KEY `user` (`user`);

--
-- Indizes für die Tabelle `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `id` (`id`),
  ADD UNIQUE KEY `tw_uid` (`tw_uid`);

--
-- AUTO_INCREMENT für exportierte Tabellen
--

--
-- AUTO_INCREMENT für Tabelle `ctguests`
--
ALTER TABLE `ctguests`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=0;
--
-- AUTO_INCREMENT für Tabelle `ctphotos`
--
ALTER TABLE `ctphotos`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=0;
--
-- AUTO_INCREMENT für Tabelle `cts`
--
ALTER TABLE `cts`
  MODIFY `id` int(6) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=0;
--
-- AUTO_INCREMENT für Tabelle `log`
--
ALTER TABLE `log`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=0;
--
-- AUTO_INCREMENT für Tabelle `news`
--
ALTER TABLE `news`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=0;
--
-- AUTO_INCREMENT für Tabelle `picreports`
--
ALTER TABLE `picreports`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=0;
--
-- AUTO_INCREMENT für Tabelle `users`
--
ALTER TABLE `users`
  MODIFY `id` int(6) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=0;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
