DROP DATABASE IF EXISTS gis_2;
CREATE DATABASE gis_2;
USE gis_2;

create table game_sessions
(
    id                  varchar(64)                 not null,
    status              ENUM('NEW', 'CHECKED', 'ACTIVE', 'CLOSED', 'ERROR') not null,
    user_id             int                         not null,
    startAmount         int                         null,
    balance             int         default 0       not null,
    currency            varchar(3)                  not null,
    platform_game_id    int                         not null,
    denomination        int         default 100     not null,
    date         timestamp   default current_timestamp() not null,
    game_config         text                        null,

    PRIMARY KEY (`id`)
);

create table users
(
    id              int auto_increment,
    sid             varchar(64)                     null,
    login           varchar(32)                     not null,
    password        varchar(32)                     not null,
    currency        varchar(3)      default 'RUB'   not null,
    balance         int             default 0       not null,
    current_session varchar(64)                     null,

    PRIMARY KEY (`id`),
    UNIQUE KEY `login_uniq` (`login`),
    UNIQUE KEY `current_session_uniq` (`current_session`)
);

create table transactions
(
    id              varchar(64)                             not null,
    type            ENUM('UNKNOWN', 'WITHDRAW', 'DEPOSIT')  not null,
    flag            ENUM('NORMAL', 'CANCELED', 'COMPLETED') not null,
    amount          int                                     not null,
    date     timestamp   default current_timestamp() not null,
    session         varchar(64)                             null,

    PRIMARY KEY (`id`)
);

INSERT INTO users (id, login, password, currency, balance) VALUES (1, 'user1', 'user1', 'RUB', 21000);
INSERT INTO users (id, login, password, currency, balance) VALUES (2, 'user2', 'user2', 'RUB', 22000);
INSERT INTO users (id, login, password, currency, balance) VALUES (3, 'user3', 'user3', 'RUB', 500);