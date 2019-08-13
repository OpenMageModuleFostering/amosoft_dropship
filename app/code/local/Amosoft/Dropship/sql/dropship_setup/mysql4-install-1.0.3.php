<?php

/**
 * Amosoft
 *
 * @category    Local
 * @package     Amosoft_Dropship
 */

$installer = $this;
$installer->startSetup();

$installer->run("
DROP TABLE IF EXISTS {$installer->getTable('dropship/supplier')};		
-- DROP TABLE IF EXISTS {$installer->getTable('dropship/inventory')}; 
CREATE TABLE IF NOT EXISTS {$installer->getTable('dropship/inventory')} (
    id INT( 11 ) NOT NULL AUTO_INCREMENT ,
    amosoft_vendor_code VARCHAR( 50 ) NOT NULL ,
    amosoft_vendor_name varchar(50) NOT NULL,
    product_sku VARCHAR( 64 ) NOT NULL ,
    amosoft_vendor_sku varchar(64) NOT NULL,
    stock INT( 11 ) NOT NULL ,
    cost decimal(12,2) NOT NULL,
    shipping_cost decimal(12,2) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ,
    updated_at TIMESTAMP NULL ,
    PRIMARY KEY (id),    
    KEY `id` (`id`),
    KEY `amosoft_vendor_code` (`amosoft_vendor_code`),
    KEY `product_sku` (`product_sku`),
    KEY `stock` (`stock`),
    KEY `cost` (`cost`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=0;

 -- DROP TABLE IF EXISTS {$installer->getTable('dropship/ranking')}; 
CREATE TABLE IF NOT EXISTS {$installer->getTable('dropship/ranking')} (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `amosoft_vendor_code` varchar(50) NOT NULL,
   amosoft_vendor_name varchar(50) NOT NULL,
  `ranking` int(11) NOT NULL DEFAULT '0',
  `amosoft_vendor_type` enum('default','enhanced','user') NOT NULL DEFAULT 'default',
  `is_dropship` enum('no','yes') NOT NULL DEFAULT 'yes',
  `is_active` enum('no','yes') NOT NULL DEFAULT 'yes',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `id` (`id`),
  KEY `amosoft_vendor_code` (`amosoft_vendor_code`),
  KEY `is_dropship` (`is_dropship`),
  KEY `ranking` (`ranking`)  
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=0 ;

-- INSERT INTO  `amosoft_vendor_ranking` (`amosoft_vendor_code` ,`amosoft_vendor_name`,`ranking`,`updated_at`)VALUES ('MagVendID1','Vendor 1',1,now()),('MagVendID2','Vendor 2',2,now()),('MagVendID3','Vendor 3',3,now()),('MagVendID4','Vendor 4',4,now()),('MagVendID5','Vendor 5',5,now()),('MagVendID6','Vendor 6',6,now()),('MagVendID7','Vendor 7',7,now()),('MagVendID8','Vendor 8',8,now()),('MagVendID9','Vendor 9',9,now()),('MagVendID10','Vendor 10',10,now()),('MagVendID11','Vendor 11',11,now()),('MagVendID12','Vendor12',12,now()),('MagVendID13','Vendor 13',13,now()),('MagVendID14','Vendor 14',14,now()),('MagVendID15','Vendor15',15,now());

 -- DROP TABLE IF EXISTS {$installer->getTable('dropship/rankinglog')}; 
CREATE TABLE IF NOT EXISTS {$installer->getTable('dropship/rankinglog')} (
    id INT( 11 ) NOT NULL AUTO_INCREMENT ,
    label VARCHAR( 50 ) NOT NULL ,
    ranking_data text NOT NULL ,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ,
    PRIMARY KEY (id),
    KEY `id` (`id`)
    
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=0;

 -- DROP TABLE IF EXISTS {$installer->getTable('dropship/ordersourcing')};
CREATE TABLE IF NOT EXISTS {$installer->getTable('dropship/ordersourcing')} (
  id int(11) NOT NULL AUTO_INCREMENT,
  order_id int(10) NOT NULL,
  sourcing enum('new','resourcing','done') NOT NULL DEFAULT 'new',
  created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  KEY `sourcing` (`sourcing`),
  KEY `id` (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=0;

 -- DROP TABLE IF EXISTS {$installer->getTable('dropship/orderitems')};
CREATE TABLE IF NOT EXISTS {$installer->getTable('dropship/orderitems')} (
  id int(11) NOT NULL AUTO_INCREMENT,
  item_id int(10) NOT NULL,
  item_order_id varchar(11) NOT NULL,
  sku varchar(64) NOT NULL,
  amosoft_vendor_sku varchar(64) NOT NULL,
  vendor_cost decimal(12,2) NOT NULL,
  shipping_cost decimal(12,2) NOT NULL,
  amosoft_item_status enum('Sourcing','Backorder','Transmitting','Sent to Vendor','Cancelled','No Dropship','Completed') NOT NULL DEFAULT 'Sourcing',
  amosoft_vendor_code varchar(50) NOT NULL,
  `updated_by` enum('Pending','User','Cron','Amosoft') NOT NULL DEFAULT 'Pending',
  created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  KEY `item_id` (`item_id`),
  KEY `amosoft_vendor_code` (`amosoft_vendor_code`),
  KEY `updated_by` (`updated_by`),
  KEY `id` (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=0;

");
//$installer->installEntities();


$installer->endSetup();
	