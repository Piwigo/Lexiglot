--
-- Structure of the table `langedit_config`
--

CREATE TABLE `langedit_config` (
  `param` varchar(32) NOT NULL,
  `value` text,
  PRIMARY KEY (`param`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Content of the table `langedit_config`
--

INSERT INTO `langedit_config` (`param`, `value`) VALUES
('install_name', 'Lexiglot'),
('default_language', 'en_UK'),
('var_name', 'lang'),
('delete_done_rows', 'false'),
('use_stats', 'true'),

('svn_activated', 'false'),
('svn_server', ''),
('svn_path', 'svn'),
('svn_user', ''),
('svn_password', ''),

('access_to_guest', 'true'),
('allow_registration', 'true'),
('allow_profile', 'true'),
('user_can_add_language', 'true'),

('user_default_language', 'own'),
('user_default_section', 'all'),
('language_default_user', 'all'),
('section_default_user', 'all'),

('new_file_content', ''),
('intro_message', 'Welcome on your new installation of Lexiglot!');

-- --------------------------------------------------------

--
-- Structure of the table `langedit_categories`
--

CREATE TABLE `langedit_categories` (
  `id` smallint(5) NOT NULL AUTO_INCREMENT,
  `name` varchar(32) NOT NULL,
  `type` enum('section','language') NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure of the table `langedit_languages`
--

CREATE TABLE `langedit_languages` (
  `id` varchar(32) NOT NULL,
  `name` varchar(64) NOT NULL,
  `flag` varchar(64),
  `rank` int(2) NOT NULL DEFAULT '1',
  `category_id` smallint(5) DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure of the table `langedit_rows`
--

CREATE TABLE `langedit_rows` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `lang` varchar(32) NOT NULL,
  `section` varchar(32) NOT NULL,
  `file_name` varchar(32) NOT NULL,
  `row_name` text CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `row_value` text CHARACTER SET utf8 COLLATE utf8_bin,
  `user_id` smallint(5) NOT NULL,
  `last_edit` datetime NOT NULL,
  `status` enum('new','edit','done') NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQUE` (`user_id`,`lang`,`section`,`file_name`,`row_name`(128))
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure of the table `langedit_sections`
--

CREATE TABLE `langedit_sections` (
  `id` varchar(32) NOT NULL,
  `name` varchar(64) NOT NULL,
  `directory` varchar(256),
  `files` text NOT NULL,
  `rank` int(2) NOT NULL DEFAULT '1',
  `category_id` smallint(5) DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure of the table `langedit_stats`
--

CREATE TABLE `langedit_stats` (
  `section` varchar(32) NOT NULL,
  `language` varchar(32) NOT NULL,
  `date` datetime NOT NULL,
  `value` float DEFAULT NULL,
  UNIQUE KEY `UNIQUE` (`section`,`language`,`date`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure of the table `langedit_users`
--

CREATE TABLE `langedit_users` (
  `id` smallint(5) NOT NULL AUTO_INCREMENT,
  `username` varchar(32) NOT NULL,
  `password` varchar(32) DEFAULT NULL,
  `email` varchar(64) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure of the table `langedit_user_infos`
--

CREATE TABLE `langedit_user_infos` (
  `user_id` smallint(5) NOT NULL,
  `registration_date` datetime NOT NULL,
  `status` varchar(16) NOT NULL,
  `languages` text DEFAULT '',
  `sections` text DEFAULT '',
  `my_languages` text DEFAULT '',
  `manage_sections` text DEFAULT '',
  `manage_perms` varchar(256) DEFAULT '',
  `nb_rows` smallint(5) NOT NULL DEFAULT '15',
  `email_privacy` enum('public','hidden','private') NOT NULL DEFAULT 'hidden',
  PRIMARY KEY (`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
