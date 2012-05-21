--
-- Structure of the table `lexiglot_config`
--

CREATE TABLE `lexiglot_config` (
  `param` varchar(32) NOT NULL,
  `value` text,
  PRIMARY KEY (`param`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Content of the table `lexiglot_config`
--

INSERT INTO `lexiglot_config` (`param`, `value`) VALUES
('install_name', 'Lexiglot'),
('default_language', 'en_UK'),
('var_name', 'lang'),
('allow_edit_default', 'false'),
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
-- Structure of the table `lexiglot_categories`
--

CREATE TABLE `lexiglot_categories` (
  `id` smallint(5) NOT NULL AUTO_INCREMENT,
  `name` varchar(32) NOT NULL,
  `type` enum('section','language') NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure of the table `lexiglot_languages`
--

CREATE TABLE `lexiglot_languages` (
  `id` varchar(32) NOT NULL,
  `name` varchar(64) NOT NULL,
  `flag` varchar(64),
  `rank` int(2) NOT NULL DEFAULT '1',
  `category_id` smallint(5) DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure of the table `lexiglot_mail_history`
--

CREATE TABLE `lexiglot_mail_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `send_date` datetime NOT NULL,
  `from_mail` varchar(255) NOT NULL,
  `to_mail` varchar(255) NOT NULL,
  `subject` text,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure of the table `lexiglot_rows`
--

CREATE TABLE `lexiglot_rows` (
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
-- Structure of the table `lexiglot_sections`
--

CREATE TABLE `lexiglot_sections` (
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
-- Structure of the table `lexiglot_stats`
--

CREATE TABLE `lexiglot_stats` (
  `section` varchar(32) NOT NULL,
  `language` varchar(32) NOT NULL,
  `date` datetime NOT NULL,
  `value` float DEFAULT NULL,
  UNIQUE KEY `UNIQUE` (`section`,`language`,`date`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure of the table `lexiglot_users`
--

CREATE TABLE `lexiglot_users` (
  `id` smallint(5) NOT NULL AUTO_INCREMENT,
  `username` varchar(32) NOT NULL,
  `password` varchar(32) DEFAULT NULL,
  `email` varchar(64) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure of the table `lexiglot_user_infos`
--

CREATE TABLE `lexiglot_user_infos` (
  `user_id` smallint(5) NOT NULL,
  `registration_date` datetime NOT NULL,
  `status` varchar(16) NOT NULL,
  `languages` text DEFAULT '',
  `sections` text DEFAULT '',
  `my_languages` text DEFAULT '',
  `manage_perms` text DEFAULT '',
  `nb_rows` smallint(5) NOT NULL DEFAULT '15',
  `email_privacy` enum('public','hidden','private') NOT NULL DEFAULT 'hidden',
  PRIMARY KEY (`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
