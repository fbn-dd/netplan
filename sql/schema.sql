SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Datenbank: `netplan`
--

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `channel`
--

DROP TABLE IF EXISTS `channel`;
CREATE TABLE IF NOT EXISTS `channel` (
  `id_channel` int(11) NOT NULL AUTO_INCREMENT,
  `id_medium` int(11) NOT NULL DEFAULT '0',
  `description` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id_channel`),
  KEY `channel_ibfk_1` (`id_medium`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=71 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `device`
--

DROP TABLE IF EXISTS `device`;
CREATE TABLE IF NOT EXISTS `device` (
  `id_device` int(11) NOT NULL AUTO_INCREMENT,
  `description` varchar(8) NOT NULL DEFAULT '',
  PRIMARY KEY (`id_device`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=65543 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `interface`
--

DROP TABLE IF EXISTS `interface`;
CREATE TABLE IF NOT EXISTS `interface` (
  `id_interface` int(11) NOT NULL AUTO_INCREMENT,
  `id_channel` int(11) NOT NULL DEFAULT '0',
  `id_node` int(11) unsigned NOT NULL DEFAULT '0',
  `id_device` int(11) NOT NULL DEFAULT '1',
  `id_mode` int(11) NOT NULL DEFAULT '2',
  `id_netmask` int(11) NOT NULL DEFAULT '24',
  `id_polarisation` int(11) NOT NULL DEFAULT '0',
  `id_vlan` int(11) NOT NULL DEFAULT '1',
  `ip` varchar(16) NOT NULL DEFAULT '',
  `dhcp_start` text,
  `dhcp_end` text,
  `description` text,
  `isWAN` enum('0','1') NOT NULL DEFAULT '0',
  `oid_override` enum('0','1') NOT NULL DEFAULT '0',
  `oid_ifid` varchar(6) DEFAULT NULL,
  PRIMARY KEY (`id_interface`),
  KEY `interface_FKIndex1` (`id_mode`),
  KEY `interface_FKIndex2` (`id_netmask`),
  KEY `interface_FKIndex3` (`id_device`),
  KEY `interface_FKIndex4` (`id_node`),
  KEY `interface_FKIndex5` (`id_channel`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1417 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `lancom_stock`
--

DROP TABLE IF EXISTS `lancom_stock`;
CREATE TABLE IF NOT EXISTS `lancom_stock` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(20) COLLATE latin1_general_ci DEFAULT NULL,
  `serial` varchar(16) COLLATE latin1_general_ci DEFAULT NULL,
  `description` varchar(50) COLLATE latin1_general_ci DEFAULT NULL,
  `vendor` varchar(20) COLLATE latin1_general_ci DEFAULT NULL,
  `delivery_date` date DEFAULT NULL,
  `mac` varchar(17) COLLATE latin1_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `serial` (`serial`),
  KEY `description` (`description`),
  KEY `vendor` (`vendor`),
  KEY `delivery_date` (`delivery_date`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci AUTO_INCREMENT=800 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `link`
--

DROP TABLE IF EXISTS `link`;
CREATE TABLE IF NOT EXISTS `link` (
  `id_link` int(11) NOT NULL AUTO_INCREMENT,
  `id_src_interface` int(11) NOT NULL DEFAULT '0',
  `id_dst_interface` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id_link`),
  KEY `link_FKIndex1` (`id_dst_interface`),
  KEY `link_FKIndex2` (`id_src_interface`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=768 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `location`
--

DROP TABLE IF EXISTS `location`;
CREATE TABLE IF NOT EXISTS `location` (
  `id_location` int(11) NOT NULL AUTO_INCREMENT,
  `id_section` int(11) unsigned NOT NULL DEFAULT '0',
  `description` varchar(32) CHARACTER SET latin1 NOT NULL DEFAULT '',
  `street` varchar(64) CHARACTER SET latin1 NOT NULL DEFAULT '',
  `postcode` varchar(8) CHARACTER SET latin1 NOT NULL DEFAULT '',
  `city` varchar(32) CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL DEFAULT '',
  `longitude` varchar(20) CHARACTER SET latin1 NOT NULL DEFAULT '000000000000',
  `latitude` varchar(20) CHARACTER SET latin1 NOT NULL DEFAULT '000000000000',
  `contact` tinytext CHARACTER SET latin1,
  PRIMARY KEY (`id_location`),
  KEY `location_FKIndex1` (`id_section`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=210 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `medium`
--

DROP TABLE IF EXISTS `medium`;
CREATE TABLE IF NOT EXISTS `medium` (
  `id_medium` int(11) NOT NULL AUTO_INCREMENT,
  `description` varchar(8) CHARACTER SET latin1 NOT NULL DEFAULT '',
  PRIMARY KEY (`id_medium`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=6 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `mode`
--

DROP TABLE IF EXISTS `mode`;
CREATE TABLE IF NOT EXISTS `mode` (
  `id_mode` int(11) NOT NULL AUTO_INCREMENT,
  `description` varchar(32) NOT NULL DEFAULT '',
  PRIMARY KEY (`id_mode`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=7 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `netmask`
--

DROP TABLE IF EXISTS `netmask`;
CREATE TABLE IF NOT EXISTS `netmask` (
  `id_netmask` int(11) NOT NULL AUTO_INCREMENT,
  `description` varchar(16) NOT NULL DEFAULT '',
  PRIMARY KEY (`id_netmask`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=34 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `node`
--

DROP TABLE IF EXISTS `node`;
CREATE TABLE IF NOT EXISTS `node` (
  `id_node` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `id_location` int(11) NOT NULL DEFAULT '0',
  `id_type` int(11) NOT NULL DEFAULT '2',
  `description` varchar(32) NOT NULL DEFAULT '',
  `nr_inventar` varchar(16) DEFAULT NULL,
  `serial` varchar(12) DEFAULT NULL,
  `config_password` varchar(32) DEFAULT NULL,
  `snmp_community` varchar(32) NOT NULL DEFAULT 'public',
  `snmp_password` varchar(32) DEFAULT NULL,
  `radius_password` varchar(16) DEFAULT NULL,
  `x_coord` int(4) unsigned NOT NULL DEFAULT '0',
  `y_coord` int(4) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id_node`),
  KEY `node_FKIndex1` (`id_location`),
  KEY `node_FKIndex2` (`id_type`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=648 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `node_has_service`
--

DROP TABLE IF EXISTS `node_has_service`;
CREATE TABLE IF NOT EXISTS `node_has_service` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_node` int(11) NOT NULL DEFAULT '0',
  `id_service` int(11) NOT NULL DEFAULT '0',
  `parameters` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `node_has_service_FKINDEX1` (`id_node`),
  KEY `node_has_service_FKINDEX2` (`id_service`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=87 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `oid`
--

DROP TABLE IF EXISTS `oid`;
CREATE TABLE IF NOT EXISTS `oid` (
  `id_oid` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `id_oidtype` int(11) unsigned NOT NULL DEFAULT '0',
  `id_type` int(11) unsigned NOT NULL DEFAULT '0',
  `id_device` int(11) unsigned NOT NULL DEFAULT '0',
  `oid` varchar(128) NOT NULL,
  PRIMARY KEY (`id_oid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=435 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `oidtype`
--

DROP TABLE IF EXISTS `oidtype`;
CREATE TABLE IF NOT EXISTS `oidtype` (
  `id_oidtype` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `description` varchar(64) NOT NULL DEFAULT '',
  `file_prefix` varchar(64) DEFAULT NULL,
  `ds_name` varchar(64) DEFAULT NULL,
  `rrd_dst` varchar(32) DEFAULT NULL,
  `rrd_heartbeat` varchar(32) DEFAULT NULL,
  `rrd_min` varchar(32) DEFAULT NULL,
  `rrd_max` varchar(32) DEFAULT NULL,
  `id_mode` int(11) DEFAULT NULL,
  `dowalk` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id_oidtype`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=40 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `polarisation`
--

DROP TABLE IF EXISTS `polarisation`;
CREATE TABLE IF NOT EXISTS `polarisation` (
  `id_polarisation` int(11) NOT NULL AUTO_INCREMENT,
  `description` varchar(64) NOT NULL DEFAULT '',
  PRIMARY KEY (`id_polarisation`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=4 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `route`
--

DROP TABLE IF EXISTS `route`;
CREATE TABLE IF NOT EXISTS `route` (
  `id_route` int(11) NOT NULL AUTO_INCREMENT,
  `id_node` int(11) unsigned NOT NULL DEFAULT '0',
  `id_netmask` int(11) NOT NULL DEFAULT '0',
  `network` varchar(16) NOT NULL DEFAULT '',
  `description` varchar(64) DEFAULT NULL,
  `gateway` varchar(16) NOT NULL DEFAULT '',
  PRIMARY KEY (`id_route`),
  KEY `routes_FKIndex1` (`id_netmask`),
  KEY `routes_FKIndex2` (`id_node`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=814 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `section`
--

DROP TABLE IF EXISTS `section`;
CREATE TABLE IF NOT EXISTS `section` (
  `id_section` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `description` varchar(32) NOT NULL DEFAULT '',
  `tm_width` int(4) unsigned NOT NULL DEFAULT '0',
  `tm_height` int(4) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id_section`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=23 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `service`
--

DROP TABLE IF EXISTS `service`;
CREATE TABLE IF NOT EXISTS `service` (
  `id_service` int(11) NOT NULL AUTO_INCREMENT,
  `value` varchar(64) NOT NULL DEFAULT '',
  `description` varchar(64) NOT NULL DEFAULT '',
  PRIMARY KEY (`id_service`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=13 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `subnet`
--

DROP TABLE IF EXISTS `subnet`;
CREATE TABLE IF NOT EXISTS `subnet` (
  `id_subnet` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `id_route` int(11) DEFAULT NULL,
  `bnid` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id_subnet`),
  KEY `subnet_FKIndex1` (`id_route`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=134 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `type`
--

DROP TABLE IF EXISTS `type`;
CREATE TABLE IF NOT EXISTS `type` (
  `id_type` int(11) NOT NULL AUTO_INCREMENT,
  `description` varchar(32) NOT NULL DEFAULT '',
  `dot_color` varchar(7) NOT NULL DEFAULT '',
  PRIMARY KEY (`id_type`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=31 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `vlan`
--

DROP TABLE IF EXISTS `vlan`;
CREATE TABLE IF NOT EXISTS `vlan` (
  `id_vlan` int(11) unsigned NOT NULL DEFAULT '0',
  `description` text NOT NULL,
  PRIMARY KEY (`id_vlan`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Constraints der exportierten Tabellen
--

--
-- Constraints der Tabelle `channel`
--
ALTER TABLE `channel`
  ADD CONSTRAINT `channel_ibfk_1` FOREIGN KEY (`id_medium`) REFERENCES `medium` (`id_medium`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Constraints der Tabelle `interface`
--
ALTER TABLE `interface`
  ADD CONSTRAINT `interface_ibfk_1` FOREIGN KEY (`id_mode`) REFERENCES `mode` (`id_mode`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `interface_ibfk_2` FOREIGN KEY (`id_netmask`) REFERENCES `netmask` (`id_netmask`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `interface_ibfk_3` FOREIGN KEY (`id_device`) REFERENCES `device` (`id_device`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `interface_ibfk_4` FOREIGN KEY (`id_node`) REFERENCES `node` (`id_node`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `interface_ibfk_5` FOREIGN KEY (`id_channel`) REFERENCES `channel` (`id_channel`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Constraints der Tabelle `link`
--
ALTER TABLE `link`
  ADD CONSTRAINT `link_ibfk_1` FOREIGN KEY (`id_dst_interface`) REFERENCES `interface` (`id_interface`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `link_ibfk_2` FOREIGN KEY (`id_src_interface`) REFERENCES `interface` (`id_interface`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints der Tabelle `location`
--
ALTER TABLE `location`
  ADD CONSTRAINT `location_ibfk_1` FOREIGN KEY (`id_section`) REFERENCES `section` (`id_section`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints der Tabelle `node`
--
ALTER TABLE `node`
  ADD CONSTRAINT `node_ibfk_1` FOREIGN KEY (`id_location`) REFERENCES `location` (`id_location`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `node_ibfk_2` FOREIGN KEY (`id_type`) REFERENCES `type` (`id_type`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Constraints der Tabelle `node_has_service`
--
ALTER TABLE `node_has_service`
  ADD CONSTRAINT `node_has_service_ibfk_1` FOREIGN KEY (`id_service`) REFERENCES `service` (`id_service`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Constraints der Tabelle `route`
--
ALTER TABLE `route`
  ADD CONSTRAINT `route_ibfk_1` FOREIGN KEY (`id_netmask`) REFERENCES `netmask` (`id_netmask`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `route_ibfk_2` FOREIGN KEY (`id_node`) REFERENCES `node` (`id_node`) ON DELETE CASCADE ON UPDATE CASCADE;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
