-- password manager database
create database if not exists `password_manager`
character set utf8mb4 collate utf8mb4_unicode_ci;

use `password_manager`;

-- users table
create table if not exists `users` (
  `id` int not null auto_increment,
  `username` varchar(50) not null,
  `password` varchar(255) not null comment 'bcrypt hashed password',
  `encryption_key` text not null comment 'aes-256 encrypted master key',
  `created_at` datetime default current_timestamp,
  `updated_at` datetime default current_timestamp on update current_timestamp,
  primary key (`id`),
  unique key `username_unique` (`username`)
) engine=innodb default charset=utf8mb4;

-- stored passwords table
create table if not exists `stored_passwords` (
  `id` int not null auto_increment,
  `user_id` int not null,
  `service_name` varchar(100) not null,
  `service_username` varchar(100),
  `encrypted_password` text not null,
  `url` varchar(255),
  `notes` text,
  `category` varchar(50),
  `favorite` boolean default false,
  `created_at` datetime default current_timestamp,
  `updated_at` datetime default current_timestamp on update current_timestamp,
  primary key (`id`),
  foreign key (`user_id`) references `users` (`id`) on delete cascade,
  index `user_service_idx` (`user_id`, `service_name`)
) engine=innodb default charset=utf8mb4;

-- password settings table
create table if not exists `password_settings` (
  `user_id` int not null,
  `length` tinyint unsigned default 12,
  `use_uppercase` boolean default true,
  `use_lowercase` boolean default true,
  `use_numbers` boolean default true,
  `use_special` boolean default true,
  primary key (`user_id`),
  foreign key (`user_id`) references `users` (`id`) on delete cascade,
  constraint `length_check` check (`length` between 8 and 64)
) engine=innodb default charset=utf8mb4;

-- activity log table
create table if not exists `activity_log` (
  `id` int not null auto_increment,
  `user_id` int,
  `action` varchar(50) not null,
  `details` text,
  `ip_address` varchar(45),
  `created_at` datetime default current_timestamp,
  primary key (`id`),
  foreign key (`user_id`) references `users` (`id`) on delete set null
) engine=innodb default charset=utf8mb4;