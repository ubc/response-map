--
-- PostgreSQL database cluster dump
--

\restrict rdcHQam3cDgbUD3faXjhobKrUfBvergtv5hgdCnTemR4bZGhl2JRC1qi3Va7caW

SET default_transaction_read_only = off;

SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;

--
-- Roles
--

CREATE ROLE canvas_readonly_user;
ALTER ROLE canvas_readonly_user WITH NOSUPERUSER INHERIT NOCREATEROLE NOCREATEDB LOGIN NOREPLICATION NOBYPASSRLS;






\unrestrict rdcHQam3cDgbUD3faXjhobKrUfBvergtv5hgdCnTemR4bZGhl2JRC1qi3Va7caW

--
-- PostgreSQL database cluster dump complete
--

