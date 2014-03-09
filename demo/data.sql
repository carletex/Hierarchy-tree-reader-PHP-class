CREATE TABLE IF NOT EXISTS `tree` (
  `id` int(11) NOT NULL,
  `parent_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `tree` (`id`, `parent_id`, `name`) VALUES
	(1, 0, 'Node-1'),
	(2, 0, 'Node-2'),
	(3, 0, 'Node-3'),
	(4, 1, 'Node-1.1'),
	(5, 1, 'Node-1.2'),
	(6, 1, 'Node-1.3'),
	(7, 2, 'Node-2.1'),
	(8, 2, 'Node-2.2'),
	(9, 3, 'Node-3.1'),
	(10, 4, 'Node-1.1.1'),
	(11, 4, 'Node-1.1.2'),
	(12, 5, 'Node-1.2.1'),
	(13, 5, 'Node-1.2.2'),
	(14, 5, 'Node-1.2.3'),
	(15, 7, 'Node-2.1.1'),
	(16, 7, 'Node-2.1.2'),
	(17, 9, 'Node-3.1.1'),
	(18, 9, 'Node-3.1.2'),
	(19, 9, 'Node-3.1.3'),
	(20, 9, 'Node-3.1.4'),
	(21, 12, 'Node-1.2.1.1'),
	(22, 12, 'Node-1.2.1.2'),
	(23, 12, 'Node-1.2.1.3'),
	(24, 19, 'Node-3.1.3.1'),
	(25, 19, 'Node-3.1.3.2');
