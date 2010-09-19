-- This is your first incremental update. Incremental updates is the way how you
-- Change database once your project is started. Make sure updates are well-tested
-- before putting them here. Running "update.sh" on other machines will automaticaly
-- upgrade database
alter table user add gender char(1) default 'M';

alter table user add manager_id int;
alter table user add constraint manager_id_fk foreign key (manager_id) references user (id);
-- If you
