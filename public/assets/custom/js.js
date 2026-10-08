// Tema AirPatrol: el rediseño vive en el SCSS compilado (airpatrol.css /
// airpatrol-dark.css); este skin inyectado solo aplica a los temas legacy.
var __apThemeActive = !!document.querySelector('link[href*="airpatrol"],link[href*="gpsubicar"]');

var styleSheet = document.createElement("style");
styleSheet.type = "text/css";
styleSheet.innerText = `:root .black {
	 --inverso-base: #e4e3e3;
	 --base: #1b1c1c;
	 --light: #3b3d3d;
	 --medium: #2f3030;
	 --dark: #141414;
	 --darkness: #000000;
	 --white: #ffffff;
	 --black: #363636;
	 --gray: #3e3e3e
}

:root .blue {
	 --inverso-base: #1B99BD;
	 --base: #052062;
	 --light: #1CA4C9;
	 --medium: #1785A3;
	 --dark: #11667D;
	 --darkness: #09323D;
	 --white: #ffffff;
	 --black: #363636;
	 --gray: #3e3e3e;
	
}

:root .cyan {
	 --inverso-base: #ebc034;
	 --base: #34c0eb;
	 --light: #4cd8ff;
	 --medium: #2ba6c6;
	 --dark: #1d7593;
	 --darkness: #124a5d;
	 --white: #ffffff;
	 --black: #363636;
	 --gray: #3e3e3e;
	
}

:root .golden {
	 --inverso-base: #205fdf;
	 --base: #dfb020;
	 --light: #f3c94c;
	 --medium: #b98e1a;
	 --dark: #8c6b13;
	 --darkness: #5e470d;
	 --white: #ffffff;
	 --black: #363636;
	 --gray: #3e3e3e;
	
}

:root .gray {
	 --inverso-base: #60a14b;
	 --base: #455560;
	 --light: #5e6e7b;
	 --medium: #37434c;
	 --dark: #2b343b;
	 --darkness: #1c2227;
	 --white: #ffffff;
	 --black: #363636;
	 --gray: #3e3e3e;
	
}

:root .green {
	 --inverso-base: #d842ba;
	 --base: #27BD45;
	 --light: #2AC94A;
	 --medium: #22A33C;
	 --dark: #1A7D2E;
	 --darkness: #0D3D17;
	 --white: #ffffff;
	 --black: #363636;
	 --gray: #3e3e3e;
	
}

:root .indigo {
	 --inverso-base: #94d06e;
	 --base: #6B2F91;
	 --light: #9B43D1;
	 --medium: #632B85;
	 --dark: #4F226B;
	 --darkness: #331645;
	 --white: #ffffff;
	 --black: #363636;
	 --gray: #3e3e3e;
	
}

:root .orange {
	 --inverso-base: #196aff;
	 --base: #E69500;
	 --light: #FFA500;
	 --medium: #E69500;
	 --dark: #805300;
	 --darkness: #402900;
	 --white: #ffffff;
	 --black: #363636;
	 --gray: #3e3e3e;
	
}

:root .pink {
	 --inverso-base: #19ed7c;
	 --base: #E61283;
	 --light: #ff69b4;
	 --medium: #BF0F6D;
	 --dark: #800A49;
	 --darkness: #400524;
	 --white: #ffffff;
	 --black: #363636;
	 --gray: #3e3e3e;
	
}

:root .purple {
	 --inverso-base: #a66b1d;
	 --base: #6b1daa;
	 --light: #8434c3;
	 --medium: #541684;
	 --dark: #3e0f63;
	 --darkness: #29093e;
	 --white: #ffffff;
	 --black: #363636;
	 --gray: #3e3e3e;
	
}

:root .red {
	 --inverso-base: #00ffff;
	 --base: #FF0000;
	 --light: #fd4444;
	 --medium: #960101;
	 --dark: #5a0000;
	 --darkness: #3b0000;
	 --white: #ffffff;
	 --black: #363636;
	 --gray: #3e3e3e;
	
}

:root .win10 {
	 --inverso-base: #e37e19;
	 --base: #1C81E6;
	 --light: #42a1ff;
	 --medium: #176BBF;
	 --dark: #1f62a6;
	 --darkness: #082440;
	 --white: #ffffff;
	 --black: #363636;
	 --gray: #3e3e3e;
	
}

*,
body {
	 font-family: \"Open Sans\", sans-serif;
	
}

.btn-group.dropleft.droparrow.open .dropdown-menu a[data-modal=\"send_command\"],
.btn-group.dropleft.droparrow.open .dropdown-menu a[data-modal=\"sharing\"],
.btn-group.dropleft.droparrow.open .dropdown-menu a[data-id=\"geofencing_tab\"] {
	 background-image: none !important;
	
}

i.icon.textual {
	 background-image: url(assets/custom/icons/textual.svg) !important;
	 background-size: contain;
	 background-position: center;
	 background-repeat: no-repeat;
	
}

i.icon.textual::before {
	 opacity: 0;
	
}

i.icon.door {
	 background-image: url(assets/custom/icons/door.svg) !important;
	 background-size: contain;
	 background-position: center;
	 background-repeat: no-repeat;
	
}

i.icon.door::before {
	 opacity: 0;
	
}

#sidebar .group:last-of-type {
	 margin-bottom: 60px;
	
}

.dropdown-menu .bs-searchbox input {
	 border-radius: 10px !important;
	
}

#sidebar .dropdown-menu li a::after {
	 display: none;
	
}

div#alerts_tab span.input-group-btn {
	 display: inline-block;
	 width: 100%;
	 margin-top: 5px;
	
}

[data-modal=\"alerts_create\"] {
	 border-radius: 20px !important;
	 width: 100%;
	 margin: 0 !important;
	 background-color: var(--base);
	 color: var(--white);
	
}

div#alerts_tab span.input-group-btn,
div#events_tab span.input-group-btn {
	 display: inline-block;
	 width: 100%;
	 margin-top: 5px;
	 margin-bottom: 10px;
	 text-align: right;
	
}

[data-modal=\"events_lookup\"] {
	 border-radius: 40px !important;
	 width: 48%;
	 margin: 0 !important;
	 background-color: var(--base);
	 color: var(--white);
	
}

[data-modal=\"events_do_destroy\"] {
	 border-radius: 40px !important;
	 width: 48%;
	 margin: 0;
	 background-color: var(--base);
	 color: var(--white);
	
}

.details a[data-modal=\"send_command\"],
.details a[data-modal=\"sharing\"],
.details a[data-id=\"geofencing_tab\"] {
	 background: var(--darkness) !important;
	 filter: invert(0) !important;
	 background-image: none !important;
	
}



/* .datepicker.datepicker-dropdown.dropdown-menu.datepicker-orient-left.datepicker-orient-bottom {    background: var(--white);} */
.dropdown-menu>li>a:focus,
.dropdown-menu>li>a:hover,
.leaflet-control-layers .leaflet-control-layers-list>li>a:focus,
.leaflet-control-layers .leaflet-control-layers-list>li>a:hover {
	 background: 0 0 !important;
	
}

i.icon.speed {
	 background-image: url(assets/custom/icons/speed.svg) !important;
	 background-size: contain;
	 background-position: center;
	 background-repeat: no-repeat;
	
}

i.icon.speed::before {
	 opacity: 0;
	
}

.on i.icon.detect_engine,
i.icon.acc {
	 background-image: url(assets/custom/icons/acc.svg) !important;
	 background-size: contain;
	 background-position: center;
	 background-repeat: no-repeat;
	
}

.off i.icon.detect_engine {
	 background-image: url(assets/custom/icons/acc_off.svg) !important;
	 background-size: contain;
	 background-position: center;
	 background-repeat: no-repeat;
	
}

i.icon.acc::before,
i.icon.detect_engine::before {
	 opacity: 0;
	
}

i.icon.odometer {
	 background-image: url(assets/custom/icons/odometer.svg) !important;
	 background-size: contain;
	 background-position: center;
	 background-repeat: no-repeat;
	
}

i.icon.odometer::before {
	 opacity: 0;
	
}

i.icon.battery {
	 background-image: url(assets/custom/icons/battery.svg) !important;
	 background-size: contain;
	 background-position: center;
	 background-repeat: no-repeat;
	
}

i.icon.battery::before {
	 opacity: 0;
	
}

i.icon.battery::after {
	 opacity: 0;
	
}

i.icon.logical {
	 background-image: url(assets/custom/icons/logical.svg) !important;
	 background-size: contain;
	 background-position: center;
	 background-repeat: no-repeat;
	
}

i.icon.logical::before {
	 opacity: 0;
	
}

i.icon.engine_hours {
	 background-image: url(assets/custom/icons/engine_hours.svg) !important;
	 background-size: contain;
	 background-position: center;
	 background-repeat: no-repeat;
	
}

i.icon.engine_hours::before {
	 opacity: 0;
	
}

i.icon.satellites {
	 background-image: url(assets/custom/icons/satellites.svg) !important;
	 background-size: contain;
	 background-position: center;
	 background-repeat: no-repeat;
	
}

i.icon.satellites::before {
	 opacity: 0;
	
}

.lang-list .lang-item .btn {
	 border: none;
	 background: 0 0;
	
}

.datetime .date,
.datetime .time {
	 font-size: 0.9rem;
	
}

.table>tbody>tr>td,
.table>tbody>tr>th,
.table>tfoot>tr>td,
.table>tfoot>tr>th,
.table>thead>tr>td,
.table>thead>tr>th {
	 padding: 5px;
	
}

div#device-add-form-advanced .dropdown-menu ul li a {
	 color: var(--gray);
	 font-weight: 600;
	
}

div#device-add-form-advanced .dropdown-menu {
	 background: var(--white);
	
}

div#device-add-form-main .dropdown-menu ul li a {
	 color: var(--gray);
	 font-weight: 600;
	
}

div#device-add-form-main .dropdown-menu {
	 background: var(--white);
	
}

div#history_tab .dropdown-menu .hidden {
	 display: none !important;
	
}

.light div#history_tab .dropdown-menu {
	 background: var(--white);
	
}

.light div#history_tab .dropdown-menu li a {
	 color: var(--gray);
	 font-weight: 600;
	
}

div#alerts-form-geofences ul.dropdown-menu.inner {
	 border-radius: 20px;
	 border: 1px solid #e2e2e2;
	
}

.light div#alerts-form-geofences .dropdown-menu {
	 background: var(--white);
	
}

.light div#alerts-form-geofences .dropdown-menu ul li a {
	 color: var(--gray);
	 font-weight: 600;
	
}

div#alerts-form-geofences ul.dropdown-menu.inner {
	 border-radius: 20px;
	 border: 1px solid #e2e2e2;
	
}

.light div#alerts-form-geofences .dropdown-menu {
	 background: var(--white);
	
}

.sidebar-content a[data-modal=\"alerts\"] {
	 background-image: url(assets/custom/icons/alert.svg) !important;
	 background-repeat: no-repeat !important;
	 background-size: 35px !important;
	 background-position: center 15px !important;
	 background: var(--base);
	
}

.sidebar-content a[data-modal=\"send_command\"] {
	 background-image: url(assets/custom/icons/cmd.png) !important;
	 background-repeat: no-repeat !important;
	 background-size: 35px !important;
	 background-position: center 15px !important;
	 background: var(--base);
	
}

.sidebar-content a[data-modal=\"sharing\"] {
	 background-image: url(assets/custom/icons/share.png) !important;
	 background-repeat: no-repeat !important;
	 background-size: 35px !important;
	 background-position: center 15px !important;
	 background: var(--base);
	
}

.sidebar-content a[data-id=\"geofencing_tab\"] {
	 background-image: url(assets/custom/icons/polygone.png) !important;
	 background-repeat: no-repeat !important;
	 background-size: 35px !important;
	 background-position: center 15px !important;
	 background: var(--base);
	 display: block !important;
	
}

.sidebar-content a[data-modal=\"reports_create\"] {
	 background-image: url(assets/custom/icons/reports.svg) !important;
	 background-repeat: no-repeat !important;
	 background-size: 35px !important;
	 background-position: center 15px !important;
	 background: var(--base);	
}



a[href=\"#pois_create\"] {
	 display: none !important;
	
}

a[href=\"#pois_edit\"] {
	 display: none !important;
	
}

a[href=\"#geofencing_tab\"] {
	 display: none !important;
	
}

a[href=\"#geofencing_create\"] {
	 display: none !important;
	
}
#geofencing_create .tab-pane-body{
min-height: 900px;
}


a[href=\"#geofencing_edit\"] {
	 display: none !important;
	
}

// a[href=\"#routes_tab\"] {
// 	 display: none !important;
	
// }

a[href=\"#routes_create\"] {
	 display: none !important;
	
}

a[href=\"#routes_edit\"] {
	 display: none !important;
	
}

.sign-in-layout .btn {
	 font-size: 1.1em;
	 font-weight: 700;
	 border-radius: 10px;
	
}

#password-reminder-form input[name=\"email\"],
input#sign-in-form-email {
	 background-image: url(assets/custom/icons/email.svg) !important;
	 background-size: 20px;
	 background-position: 95% center;
	 background-repeat: no-repeat;
	 background-color: rgba(255, 255, 255, 0.45);
	
}

input#sign-in-form-password {
	 background-image: url(assets/custom/icons/password.svg) !important;
	 background-size: 20px;
	 background-position: 95% center;
	 background-repeat: no-repeat;
	 background-color: rgba(255, 255, 255, 0.45);
	
}

.sign-in-layout .form hr {
	 margin: 5px;
	
}

.sign-in-layout .form-control {
	 font-size: 1.2em;
	 height: 40px;
	 font-weight: 600;
	
}

.sign-in-layout img.img-responsive.center-block {
	 width: auto;
	 max-width: 270px;
	
}

.sign-in-layout .panel,
body.sign-in-layout .plan {
	 box-shadow: none !important;
	
}

.sign-in-layout .center-vertical {
	 height: 100% !important;
	
}

.sign-in-layout .container {
	 height: 80vh;
	 width: 350px;
	 margin-right: 0;
	 padding: 0;
	 display: flex;
	 margin-top: 10vh;
	 margin-right: 5vh;
	 border-radius: 20px;
	
}

.sign-in-layout.light .container {
	 background: rgba(255, 255, 255, 60%);
	
}

.sign-in-layout.dark .container {
	 background: rgba(0, 0, 0, 60%);
	
}

.sign-in-layout .container .col-xs-12.col-sm-8.col-sm-offset-2.col-md-6.col-md-offset-3.col-lg-4.col-lg-offset-4 {
	 margin: auto;
	 width: 100%;
	 padding: 0;
	 background: 0 0;
	
}

.light .popup-body table {
	 font-family: \"Open Sans\", sans-serif;
	 color: #000;
	
}

.light .popup-body .table tbody>tr>th {
	 font-weight: 700 !important;
	 color: var(--dark);
	
}

.popup-header .nav-default {
	 background-color: transparent;
	
}

.popup-header {
	 background: var(--base);
	
}

.leaflet-popup {
	 margin-bottom: 30px;
	
}

.leaflet-popup-content {
	 border-radius: 20px;
	 overflow: hidden;
	
}

.popup-header .popup-title {
	 background: 0 0;
	 color: var(--white);
	 font-size: 1.8em;
	 padding: 5px 0 5px 10px;
	
}

.light .nav-default>li.active:after {
	 background: var(--white);
	 min-width: 65px;
	
}

.dark .nav-default>li.active:after {
	 background: var(--black);
	 min-width: 65px;
	
}

.popup-header .nav-tabs>li.active>a {
	 background: 0 0 !important;
	 border: none !important;
	
}

.popup-header .nav-default>li>a {
	 background: 0 0;
	 border: none;
	
}

.popup-header .nav-default>li>a i {
	 color: var(--white);
	
}

#sidebar.collapsed a.btn-collapse {
	 margin-left: 10px;
	
}

#sidebar.collapsed .input-group {
	 display: none;
	
}

#sidebar a.btn-collapse {
	 background: var(--base);
	 font-size: 1.4em;
	 color: var(--white);
	 height: 60px;
	 line-height: 60px;
	 margin-left: -10px;
	 width: 30px;
	 border-radius: 30px;
	
}

#sidebar .btn-collapse:before,
.btn-collapse.collapse-left:before {
	 display: none;
	
}

#sidebar .btn-collapse:after,
.btn-collapse.collapse-left:after {
	 display: none;
	
}

#widgets .btn-collapse:before,
.btn-collapse.collapse-top:before {
	 display: none;
	
}

#widgets .btn-collapse:after,
.btn-collapse.collapse-top:after {
	 display: none;
	
}

#widgets .btn-collapse {
	 width: 60px;
	 background: var(--base);
	 font-size: 1.4em;
	 color: var(--white);
	 border-radius: 30px;
	 height: 30px;
	 line-height: 30px;
	
}

.leaflet-control-layers .leaflet-control-layers-list,
id#map-controls .dropdown-menu {
	 color: var(--white);
	
}

[data-device=\"preview\"] a {
	 background-repeat: no-repeat;
	 height: 20px;
	 width: 20px;
	 border: none;
	 background-size: cover;
	
}

.light [data-device=\"preview\"] a {
	 background-image: url(assets/custom/icons/preview.svg) !important;
	
}

.dark [data-device=\"preview\"] a {
	 background-image: url(assets/custom/icons/preview-black.svg) !important;
	
}

[data-device=\"preview\"] i {
	 opacity: 0;
	
}

.modal-header button.close span {
	 font-size: 2em;
	 position: relative;
	 top: -5px;
	
}

.modal-header button.close {
	 color: var(--base);
	 opacity: 1;
	 font-size: 1.8em;
	 background: var(--white);
	 width: 35px;
	 border-radius: 50px;
	 height: 35px;
	
}

.light .modal-body .nav-default {
	 background-color: var(--dark);
	 padding: 5px;
	
}

.light table.table.table-list tr:nth-child(2n + 2) {
	 background: #f2f2f2;
	
}

.table.table-list>thead>tr {
	 color: var(--white);
	 background-color: var(--base) !important;
	
}

.form-group label {
	 font-weight: 600;
	
}

.form-control {
	 border-radius: 7px;
	 background: 0 0;
	
}

.light .form-control {
	 border: 1px solid #d4d4d4;
	 height: 31px;
	
}

.dark .form-control {
	 border: 1px solid #696969;
	 height: 31px;
	
}

.modal-body .nav-default>li.active:after {
	 display: none;
	
}

.modal-body .nav-tabs>li {
	 margin: 5px;
	
}

.modal-body .nav-tabs>li>a:hover {
	 border: 2px solid var(--white);
	
}

.modal-body .nav-tabs>li>a {
	 border-radius: 30px;
	 background: 0 0;
	 font-size: 1em;
	 border: 2px solid #ffffff36;
	 padding: 5px 10px;
	
}

.modal-body .nav-tabs>li>a[data-toggle=\"tab\"] {
	 color: white;
	
}

.modal-body .nav-tabs>li.active>a,
.nav-tabs>li.active>a:focus,
.nav-tabs>li.active>a:hover {
	 background: var(--white);
	 color: #000;
	 border: 2px solid transparent;
	 font-weight: bold;
	
}

.group-list>li> :last-child {
	 border: none;
	
}

.group-list>li.active> :last-child {
	 border: none !important;
	
}

.group-list>li.active .name {
	 font-weight: 700 !important;
	
}

.group-list>li.active .name [data-device=\"time\"] {
	 font-weight: 100 !important;
	
}

.group-list>li.active {
	 background: #dedede;
	
}

.details .btn.icon {
	 font-size: 1.2em;
	 padding: 0;
	 font-weight: 700;
	
}

.details .btn.icon:hover {
	 background: 0 0;
	
}

.btn-group.bootstrap-select.show-tick.form-control.multiexpand {
	 background: 0 0;
	
}

div#alerts-form-geofences ul.dropdown-menu.inner {
	 border-radius: 20px;
	 border: 1px solid #e2e2e2;
	
}

.light div#alerts-form-geofences .dropdown-menu {
	 background: var(--white);
	
}

.dark div#alerts-form-geofences .dropdown-menu {
	 background: var(--black);
	
}

div#alerts-form-user .dropdown-menu ul li a {
	 font-weight: 600;
	
}

.light div#alerts-form-user .dropdown-menu ul li a {
	 color: var(--gray);
	
}

.dark div#alerts-form-user .dropdown-menu ul li,
.dark div#alerts-form-user .dropdown-menu ul li a {
	 color: var(--white);
	
}

div#alerts-form-user ul.dropdown-menu.inner {
	 border-radius: 20px;
	 border: 1px solid #e2e2e2;
	
}

.light div#alerts-form-user .dropdown-menu {
	 background: var(--white);
	
}

.dark div#alerts-form-user .dropdown-menu {
	 background: var(--black);
	
}

.light div#devices_groups_edit .dropdown-menu ul li a {
	 color: var(--gray);
	 font-weight: 600;
	
}

div#devices_groups_edit ul.dropdown-menu.inner {
	 border-radius: 20px;
	 border: 1px solid #e2e2e2;
	
}

.light div#devices_groups_edit .dropdown-menu {
	 background: var(--white);
	
}

div#devices_groups_create .dropdown-menu ul li a {
	 font-weight: 600;
	
}

div#devices_groups_create ul.dropdown-menu.inner {
	 border-radius: 20px;
	 border: 1px solid #e2e2e2;
	
}

.bs-searchbox input {
	 border: 1px solid #d2d2d2 !important;
	 border-radius: 20px;
	
}

.light .modal-content {
	 background: 0 0;
	 box-shadow: none;
	 border: none;
	
}

.modal-header {
	 background: var(--base);
	 color: var(--white);
	 border-radius: 15px 15px 0 0;
	
}

.light .modal-body {
	 background: var(--white);
	
}

.modal-footer .btn {
	 border-radius: 20px;
	 text-align: right;
	
}

.modal-footer {
	 text-align: center !important;
	 border-radius: 0 0 30px 30px;
	
}

.light .modal-footer {
	 background: var(--white);
	
}

.dark .modal-footer {
	 background: var(--black);
	
}

.modal-header h4 {
	 font-size: 1.25em;
	 font-weight: 700;
	
}

.modal-header i:before {
	 color: var(--white);
	
}

.modal-header {
	 background: var(--base);
	 color: var(--white);
	
}

button.actions-btn.bs-select-all.btn.btn-default {
	 background: 0 0;
	 border: 1px solid #e0e0e0;
	 border-radius: 20px !important;
	
}

button.actions-btn.bs-deselect-all.btn.btn-default {
	 background: 0 0;
	 border: 1px solid #e0e0e0;
	 border-radius: 20px !important;
	
}

.dark .btn-default {
	 color: white;
	
}

.dark .btn-default i {
	 color: white;
	
}

.bootstrap-select.form-control.multiexpand .bs-actionsbox,
.bootstrap-select.form-control.multiexpand .bs-searchbox input {
	 border: none;
	 margin-bottom: 10px;
	
}

.light .bootstrap-select.form-control.multiexpand .bs-actionsbox,
.light .bootstrap-select.form-control.multiexpand .bs-searchbox input {
	 background: var(--white);
	
}

.bootstrap-select.btn-group.show-tick .dropdown-menu li a span.text::after,
.bootstrap-select.btn-group.show-tick .leaflet-control-layers .leaflet-control-layers-list li a span.text::after,
.leaflet-control-layers .bootstrap-select.btn-group.show-tick .leaflet-control-layers-list li a span.text::after {
	 border-radius: 20px;
	
}

.bootstrap-select.btn-group.show-tick .dropdown-menu li a span.text::before,
.bootstrap-select.btn-group.show-tick .leaflet-control-layers .leaflet-control-layers-list li a span.text::before,
.leaflet-control-layers .bootstrap-select.btn-group.show-tick .leaflet-control-layers-list li a span.text::before {
	 border-radius: 20px;
	
}

.light li.dropdown-header {
	 background: #fff !important;
	
}

.dark li.dropdown-header {
	 background: #363636 !important;
	
}

div#device_sharing .dropdown-menu>li>a:hover {
	 background: 0 0;
	
}

div#device_sharing ul.dropdown-menu.inner {
	 border-radius: 20px;
	 border: 1px solid #e2e2e2;
	
}

div#command-form-gprs .dropdown-menu>li>a:hover {
	 background: 0 0;
	
}

.light div#command-form-gprs .dropdown-menu>li>a {
	 color: #000;
	 font-weight: 600;
	
}

.dark div#command-form-gprs .dropdown-menu>li>a {
	 color: #FFF;
	 font-weight: 600;
	
}

.light div#command-form-gpr .dropdown-menu>li>a:hover {
	 background: var(--white);
	
}

.light div#command-form-gprs .dropdown-menu {
	 background: var(--white);
	
}

.light div#device_sharing .dropdown-menu {
	 background: var(--white);
	 margin: 0;
	 padding: 0;
	
}

.dark div#command-form-gpr .dropdown-menu>li>a:hover {
	 background: var(--black);
	
}

.dark div#command-form-gprs .dropdown-menu {
	 background: var(--black);
	
}

.dark div#device_sharing .dropdown-menu {
	 background: var(--black);
	 margin: 0;
	 padding: 0;
	
}

div#device_sharing .dropdown-menu>li>a {
	 color: #000;
	 font-weight: 600;
	
}

.dropdown-menu {
	 border: none;
	 border-radius: 15px;
	 padding-top: 5px;
	 padding-bottom: 5px;
	
}

.leaflet-control-layers .leaflet-control-layers-list {
	 border: none;
	 background: var(--darkness);
	 border-radius: 15px;
	 padding-top: 5px;
	 padding-bottom: 5px;
	 color: white;
	
}

.group-list>li>.details>*+* {
	 margin-left: 0;
	
}

#widgets .table {
	 font-size: 1em;
	
}

.light #widgets .table td:last-of-type {
	 font-weight: 600;
	 color: var(--medium);
	
}

.dark #widgets .table td:last-of-type {
	 font-weight: 600;
	 color: var(--light);
	
}

.group-list li:hover .name {
	 white-space: normal !important;
	 font-weight: 700;
	 line-height: 1;
	 margin-bottom: 5px;
	
}

.group-list li.active .name [data-device=\"time\"],
.group-list li:hover .name [data-device=\"time\"] {
	 color: var(--base);
	 font-weight: 700;
	 line-height: 1;
	 width: 100px;
	 margin-bottom: 5px;
	 margin-top: 4px;
	
}

.group-list>li>.name [data-device=time_text] {
	 font-size: .85em;
	 display: block;
	 color: #999;
	
}

.leaflet-control-layers .leaflet-control-layers-list>li>a:focus,
.leaflet-control-layers .leaflet-control-layers-list>li>a:hover {
	 text-decoration: none;
	 color: var(--white);
	 background-color: var(--dark);
	 border-radius: 20px;
	
}

.dropdown-menu>li>a:focus,
.dropdown-menu>li>a:hover {
	 font-weight: bold;
	
}

.btn-group.dropleft.droparrow.open .dropdown-menu {
	 color: var(--white);
	 background: var(--darkness);
	 border: none;
	 border-radius: 15px;
	
}

ul.dropdown-menu.dropdown-menu-left {
	 color: var(--white);
	 background: var(--darkness);
	 border: none;
	 border-radius: 15px;
	
}

ul.dropdown-menu.dropdown-menu-right {
	 color: var(--white);
	 background: var(--darkness);
	 border: none;
	 border-radius: 15px;
	
}

#ajax-items .dropdown-menu>li>a,
.leaflet-control-layers .leaflet-control-layers-list>li>a {
	 color: var(--white);
	
}

#header .main-navbar .navbar-nav>.open>a,
#header .main-navbar .navbar-nav>.open>a:focus,
#header .main-navbar .navbar-nav>.open>a:hover,
.navbar-main .navbar-nav>.open>a,
.navbar-main .navbar-nav>.open>a:focus,
.navbar-main .navbar-nav>.open>a:hover {
	 background: var(--dark);
	
}

#header .main-navbar .navbar-nav>li>a:focus,
#header .main-navbar .navbar-nav>li>a:hover,
.navbar-main .navbar-nav>li>a:focus,
.navbar-main .navbar-nav>li>a:hover {
	 background: var(--dark);
	
}

[data-modal=\"language-selection\"] {
	 text-align: center;
	 background: var(--base);
	 padding: 0 !important;
	 border-radius: 100px;
	 margin-left: 5px;
	 width: 35px;
	 height: 35px;
	 top: 5px;
	 margin-right: 5px;
	 line-height: 35px !important;
	
}

[data-modal=\"language-selection\"] img {
	 border: none !important;
	 padding: 0 !important;
	
}

span.icon.chat:before {
	 opacity: 0;
	
}

span.icon.chat {
	 background-image: url(assets/custom/icons/chat.svg) !important;
	 background-size: contain;
	 filter: invert(1);
	 background-repeat: no-repeat;
	
}

[data-modal=\"chat\"] {
	 background: var(--base);
	 border-radius: 100px;
	 width: 35px;
	 height: 35px;
	 padding: 0 !important;
	 line-height: 35px !important;
	 text-align: center;
	 position: relative;
	 top: 5px;
	 right: -1px;
	 margin-left: 5px;
	
}

span.icon.account:before {
	 opacity: 0;
	
}

span.icon.account {
	 background-image: url(assets/custom/icons/account.svg) !important;
	 background-size: contain;
	 filter: invert(1);
	 background-repeat: no-repeat;
	
}

[id=\"dropMyAccount\"] {
	 background: var(--base);
	 border-radius: 100px;
	 width: 35px;
	 height: 35px;
	 padding: 0 !important;
	 line-height: 35px !important;
	 text-align: center;
	 position: relative;
	 top: 5px;
	 right: -1px;
	 margin-left: 5px;
	
}

span.icon.setup:before {
	 opacity: 0;
	
}

span.icon.setup {
	 background-image: url(assets/custom/icons/setup.svg) !important;
	 background-size: contain;
	 filter: invert(1);
	 background-repeat: no-repeat;
	
}

[data-modal=\"my_account_settings_edit\"] {
	 background: var(--base);
	 border-radius: 100px;
	 width: 35px;
	 height: 35px;
	 padding: 0 !important;
	 line-height: 35px !important;
	 text-align: center;
	 position: relative;
	 top: 5px;
	 right: -1px;
	 margin-left: 5px;
	
}

span.icon.admin:before {
	 opacity: 0;
	
}

span.icon.admin {
	 background-image: url(assets/custom/icons/admin.svg) !important;
	 background-size: contain;
	 filter: invert(1);
	 background-repeat: no-repeat;
	
}

[data-original-title=\"ADMIN\"] {
	 background: var(--base);
	 border-radius: 100px;
	 width: 35px;
	 height: 35px;
	 padding: 0 !important;
	 line-height: 35px !important;
	 text-align: center;
	 position: relative;
	 top: 5px;
	 right: -1px;
	 margin-left: 5px;
	
}

span.icon.tools:before {
	 opacity: 0;
	
}

span.icon.tools {
	 background-image: url(assets/custom/icons/tools.svg) !important;
	 background-size: contain;
	 filter: invert(1);
	 background-repeat: no-repeat;
	
}

 #header .main-navbar .navbar-nav>li>a,
.navbar-main .navbar-nav>li>a#dropTools {
	 background: var(--base);
	 border-radius: 100px;
	 width: 35px;
	 height: 35px;
	 padding: 0 !important;
	 line-height: 35px !important;
	 text-align: center;
	 position: relative;
	 top: 5px;
	 right: -1px;
	 margin-left: 5px;
	
}

#map-controls {
	 top: 7%;
	 right: 10px;
	
}

#map-controls .btn {
	 border-radius: 15px;
	 margin-bottom: 5px;
	
	/* box-shadow: 0 5px 5px var(--darkness); */
	
}

.light #map-controls .btn {
	 background: var(--white);
	
}

.dark #map-controls .btn {
	 color: var(--light);
	 background: var(--black);
	
}

.light #map-controls .btn.active {
	 color: var(--white);
	 background: var(--dark);
	
}

.dark #map-controls .btn.active {
	 color: var(--white);
	 background: var(--dark);
	
}

[onclick=\"app.history.get()\"] {
	 border-radius: 20px;
	
}

div#history_tab .dropdown .btn-default {
	 border-radius: 25px;
	
}

[onclick=\"app.history.clear()\"] {
	 border-radius: 25px !important;
	
}

.light #sidebar [data-device=\"speed\"] {
	 font-weight: 700;
	 font-size: 1em;
	 color: var(--medium);
	
}

.dark #sidebar [data-device=\"speed\"] {
	 font-weight: 700;
	 font-size: 1em;
	 color: var(--light);
	
}

.btn-action,
.btn-primary {
	 background-color: var(--base);
	 border-color: var(--base);
	
}

.nav-tabs>li>a {
	 margin-right: 0;
	
}

.group-heading>.group-title {
	 font-weight: 700;
	 font-size: 1.1rem;
	
}

.checkbox label::after {
	 border-radius: 20px !important;
	 box-shadow: 0 5px 8px var(--base) 4d;
	
}

.checkbox label::before {
	 border-radius: 20px;
	
}

#sidebar .group {
	 margin: 5px;
	 border-radius: 20px;
	 overflow: hidden;
	
}

.light #sidebar .group {
	 border: 2px solid #e0e0e0;
	
}

.dark #sidebar .group {
	 border: 2px solid #363636;
	
}

#widgets .table tr>td {
	 border: none;
	
}

#widgets .widget-title .icon {
	 border-radius: 100px;
	 background: var(--base);
	 width: 25px;
	 height: 25px;
	 display: inline-block;
	 color: var(--white);
	 line-height: 25px;
	 text-align: center;
	
}

#widgets .widget>.widget-heading {
	 background: var(--dark);
	 color: white;
	
	/*background: 0 0;*/
	 border: none;
	 font-weight: 700;
	
	/*color: var(--dark);*/
	
}

#widgets .widget {
	 border: none;
	 margin: 5px 2px;
	 border-radius: 20px;
	 overflow: hidden;
	 box-shadow: 0 5px 10px var(--base) 4d;
	
}

#widgets .widget-services .table {
	min-width: 289px;
}
	
.light #widgets .widget {
	 background: var(--white);
	
}

.dark #widgets .widget {
	 background: var(--black);
	
}

#widgets .widgets-content {
	 background: 0 0 !important;
	
}

.light #sidebar ul.nav.nav-tabs.nav-default {
	 background: var(--dark);
	
}

.dark #sidebar ul.nav.nav-tabs.nav-default {
	 background: var(--darkness);
	
}

#sidebar div#objects_tab .form-group.search:after {
	 line-height: 40px;
	 font-size: 2em;
	 margin-right: 5px;
	
}

#sidebar div#objects_tab .bs-searchbox:after {
	 line-height: 50px;
	 font-size: 2em;
	 margin-right: 5px;
	
}

#sidebar div#objects_tab .form-group.search input {
	 font-size: 1.2em;
	
}

.light #sidebar div#objects_tab .form-group.search input {
	 background-color: var(--white);
	
}

.dark #sidebar div#objects_tab .form-group.search input {
	 background-color: var(--black);
	
}

#sidebar div#objects_tab .input-group-btn {
	 display: inline-block;
	 width: 100%;
	 margin-top: 5px;
	 margin-bottom: 5px;
	
}

#sidebar div#objects_tab .input-group-btn button {
	 margin-left: 0;
	 width: 50%;
	 border: none;
	 border-radius: 20px;
	 width: 40px;
	 height: 40px;
	
}

#sidebar div#objects_tab .input-group-btn a {
	
	/* width: 48%; */
	 border: none;
	 margin: 0;
	 border-radius: 40px;
	 margin-right: 5px;
	 width: 40px;
	 height: 40px;
	 padding-top: 11px
}

#header.folded .navbar .navbar-brand,
#header.folded .navbar .navbar-nav {
	 border: none;
	
}

#sidebar .tab-content {
	 max-width: calc(100% - 65px);
	 width: auto;
	 position: absolute;
	 right: 0;
	 float: right;
	
}

#sidebar .nav-tabs li.hidden a {
	 width: 65px;
	 color: #000;
	
}

.light #sidebar .sidebar-content {
	 background: rgba(255, 255, 255, 0.55);
	
}

.dark #sidebar .sidebar-content {
	 background: rgba(0, 0, 0, 0.55);
	
}

#sidebar .sidebar-content {
	 padding-top: 45px;
	
}

.light .bs-searchbox>.form-control,
.light .form-group.search>.form-control {
	 background: rgb(255, 255, 255);
	
}

.dark .bs-searchbox>.form-control,
.dark .form-group.search>.form-control {
	 background: rgb(36, 36, 36);
	
}

.light .sidebar-content .form-horizontal,
.light .sidebar-content .table {
	 background: rgba(255, 255, 255, 0.75);
	 padding: 5px;
	 border-radius: 10px;
	
}

.dark .sidebar-content .form-horizontal,
.dark .sidebar-content .table {
	 background: rgba(36, 36, 36, 0.75);
	 padding: 5px;
	 border-radius: 10px;
	
}

a[href=\"#routes_tab\"] {
	 background-image: url(assets/custom/icons/routes.svg) !important;
	 background-repeat: no-repeat !important;
	 background-size: 35px !important;
	 background-position: center 15px !important;
	 background: var(--base);
	
}
a[href=\"#pois_tab\"] {
	 background-image: url(assets/custom/icons/marker.svg) !important;
	 background-repeat: no-repeat !important;
	 background-size: 35px !important;
	 background-position: center 15px !important;
	 background: var(--base);
	
}

a[href=\"#geofencing_create\"] {
	 background-image: url(assets/custom/icons/polygone.png) !important;
	 background-repeat: no-repeat !important;
	 background-size: 35px !important;
	 background-position: center 15px !important;
	 background: var(--base);
	 opacity: 0;
	
}

a[href=\"#geofencing_edit\"] {
	 background-image: url(assets/custom/icons/polygone.png) !important;
	 background-repeat: no-repeat !important;
	 background-size: 35px !important;
	 background-position: center 15px !important;
	 background: var(--base);

	 opacity: 0;
	
}

a[href=\"#alerts_tab\"] {
	 background-image: url(assets/custom/icons/alert.svg) !important;
	 background-repeat: no-repeat !important;
	 background-size: 35px !important;
	 background-position: center 15px !important;
	 background: var(--base);

	
}

a[href=\"#history_tab\"] {
	 background-image: url(assets/custom/icons/activity-history.png) !important;
	 background-repeat: no-repeat !important;
	 background-size: 35px !important;
	 background-position: center 15px !important;
	 background: var(--base);

	
}

#sidebar a[href=\"#objects_tab\"] {
	 background-image: url(assets/custom/icons/objects.svg) !important;
	 background-repeat: no-repeat !important;
	 background-size: 40px !important;
	 background-position: center 10px !important;
	 background: var(--base);

	
}

a[href=\"#events_tab\"] {
	 background-image: url(assets/custom/icons/siren.svg) !important;
	 background-repeat: no-repeat !important;
	 background-size: 35px !important;
	 background-position: center 15px !important;
	 background: var(--base);
	 
	
}

#sidebar .nav-tabs>li.active>a {
	 background-color: var(--inverso-base) !important;
	 border: none !important;
	 font-weight: 700;
	
}

#sidebar .nav-default>li>a {
	 border: none !important;
	 background: 0 0;
	 color: #fff;
	 font-size: 0.95em;
	 padding-top: 50px !important;
	 border-bottom: 1px solid #00000024 !important;
	 padding: 10px 5px;
	 white-space: nowrap;
	 overflow: hidden;
	 text-overflow: ellipsis;
	 font-weight: 600;
	
}

#sidebar .nav-tabs>li {
	 display: inline;
	
}

#header.folded .navbar .navbar-nav:before {
	 display: none;
	
}

#header.folded .navbar .navbar-brand:before {
	 display: none;
	
}

#sidebar ul.nav.nav-tabs.nav-default {
	 width: 65px;
	 float: left;
	 z-index: 111111;
	 position: relative;
	 height: 100%;
	 overflow-y: auto;
	
}

#sidebar .tab-content {
	 float: right;
	 width: 100%;
	
}

#sidebar {
	 max-height: calc(100% - 0px);

	
}

body.sign-in-layout .panel-background {
	 opacity: 0 !important;
	
}

body.sign-in-layout .sign-in-text {
	 text-align: center;
	 font-weight: 700;
	
}

#map-controls .btn {
	 font-size: 17px;
	
}

.light .group-list>li {
	 background: rgba(255, 255, 255, 0.85);
	
}

.dark .group-list>li {
	 background: rgba(36, 36, 36, 0.95);
	
}

#history-form input.form-control {
	 padding: 7px;
	 font-size: 11px;
	
}

#history-form .input-group>.input-group-btn>.btn {
	 width: 37px;
	 height: 32px;
	
}

.sidebar-content .input-group>.input-group-btn>.btn {
	
	/* width: 40px;    height: 40px; */
	
}

.dark #header .main-navbar .navbar-nav>li>a>.icon,
.navbar-main .navbar-nav>li>a>.icon {
	 color: var(--black);
	
}

@media only screen and (max-width: 600px) {
	 .sign-in-layout .container {
		 width: 80%;
		
	}

	 #header .main-navbar,
	 .navbar-main {
		 font-size: 13px;
		 background-color: transparent !important;
		 border-color: #d9d9d9;
		
	}

	 .navbar-header {
		 background: var(--white);
		
	}

	 ul.nav.navbar-nav.navbar-right {
		 background: var(--base);
		 margin: 10px 0;
		 border-radius: 20px;
		 padding: 10px;
		
	}

	 #header.folded .navbar {
		 box-shadow: none !important;
		 border: none;
		
	}

	 [data-modal=\"chat\"] {
		 filter: invert(1) saturate(0) brightness(2);
		
	}

	 [id=\"dropMyAccount\"] {
		 filter: invert(1) saturate(0) brightness(2);
		
	}

	 [data-modal=\"my_account_settings_edit\"] {
		 filter: invert(1) saturate(0) brightness(2);
		
	}

	 [data-original-title=\"ADMIN\"] {
		 filter: invert(1) saturate(0) brightness(2);
		
	}

	 #dropTools {
		 filter: invert(1) saturate(0) brightness(2);
		
	}

	 ul.nav.navbar-nav.navbar-right .icon+.text {
		 margin-left: 40px;
		 position: relative;
		 top: -35px;
		 width: 120px;
		 display: flex;
		
	}

	 [data-modal=\"language-selection\"] {
		 padding: 0 !important;
		 width: 50px;
		 height: 50px;
		 position: fixed !important;
		 top: 70px;
		 right: 25px;
		 background: var(--white);
		
	}

	 [data-modal=\"language-selection\"] img {
		 border: none !important;
		 padding: 0 !important;
		 width: 30px;
		 margin-top: 12px;
		
	}

	 ul.nav.navbar-nav.navbar-right li {
		 margin-bottom: 10px;
		
	}

	 ul.dropdown-menu.dropdown-menu-right li .text {
		 top: -15px !important;
		 color: var(--white);
		
	}

	 ul.dropdown-menu.dropdown-menu-right li {
		 height: 20px;
		
	}

	 ul.dropdown-menu.dropdown-menu-right li a .icon {
		 font-size: 1.5em;
		 background: var(--white);
		 width: 20px;
		 height: 20px;
		 border-radius: 20px;
		 padding: 5px;
		 color: #000;
		
	}

	 ul.nav.navbar-nav.navbar-left li {
		 margin-bottom: 10px;
		
	}

	 ul.dropdown-menu.dropdown-menu-left li .text {
		 top: -15px !important;
		 color: var(--white);
		
	}

	 ul.dropdown-menu.dropdown-menu-left li {
		 height: 20px;
		
	}

	 ul.dropdown-menu.dropdown-menu-left li a .icon {
		 font-size: 1.5em;
		 background: var(--white);
		 width: 20px;
		 height: 20px;
		 border-radius: 20px;
		 padding: 5px;
		 color: #000;
		
	}

	 ul.dropdown-menu.dropdown-menu-left a[href=\"#objects_tab\"] {
		 background-image: unset !important;
		 background: 0 0;
		 filter: unset;
		
	}

	 .bootstrap-select.form-control.multiexpand .bs-actionsbox,
	 .bootstrap-select.form-control.multiexpand .bs-searchbox {
		 width: 100%;
		
	}

	 button.navbar-toggle {
		 border: none;
		 background: var(--base) !important;
		 border-radius: 100px;
		 height: 30px;
		 width: 32px;
		
	}

	 .light button.navbar-toggle .icon-bar {
		 background-color: #fff !important;
		
	}

	 .dark button.navbar-toggle .icon-bar {
		 background-color: #363636 !important;
		
	}

	 #widgets .btn-collapse {
		 left: 10px;
		
	}

#sidebar:not(.collapsed) {
     width: 80%;
    
}

	 #sidebar ul.nav.nav-tabs.nav-default {
		 zoom: 0.9;
		
	}

	
}

@media (min-width: 768px) {
	 .input-group>.input-group-btn>.form-control.timeselect {
		 width: 70px;
		
	}

	
}

.bootstrap-select>.dropdown-toggle {
	 padding: 0px;
	 border-radius: 10px;
	 height: 29px;
	
}

.bootstrap-select.btn-group .dropdown-toggle .filter-option {
	 font-size: 11px;
	
}

.bootstrap-select.btn-group .dropdown-toggle .caret {
	 right: 5px;
	
}

.devices_list .dropdown-toggle {
	 padding: 5px 20px 5px 5px;
	 border: 1px #dedede solid;
}
.devices_list .dropdown-menu {
     width: 100%;
	
}

.checkbox input[type=\"checkbox\"]:checked+label::after,
.checkbox input[type=\"radio\"]:checked+label::after,
.checkbox-inline input[type=\"checkbox\"]:checked+label::after,
.checkbox-inline input[type=\"radio\"]:checked+label::after,
.group .checkbox input[type=\"checkbox\"]:checked+label::after,
.group .checkbox input[type=\"radio\"]:checked+label::after,
.table .checkbox input[type=\"checkbox\"]:checked+label::after,
.table .checkbox input[type=\"radio\"]:checked+label::after {
	 background-color: var(--base);
	 border-color: var(--base);
	
}

.checkbox input[type=\"checkbox\"]:checked+label::before,
.checkbox input[type=\"radio\"]:checked+label::before,
.checkbox-inline input[type=\"checkbox\"]:checked+label::before,
.checkbox-inline input[type=\"radio\"]:checked+label::before,
.group .checkbox input[type=\"checkbox\"]:checked+label::before,
.group .checkbox input[type=\"radio\"]:checked+label::before,
.table .checkbox input[type=\"checkbox\"]:checked+label::before,
.table .checkbox input[type=\"radio\"]:checked+label::before {
	 background-color: var(--base);
	
}

.light #header .main-navbar .navbar-nav .open .dropdown-menu>li>a:focus,
.light #header .main-navbar .navbar-nav .open .dropdown-menu>li>a:hover,
.light #header .main-navbar .navbar-nav .open .leaflet-control-layers .leaflet-control-layers-list>li>a:focus,
.light #header .main-navbar .navbar-nav .open .leaflet-control-layers .leaflet-control-layers-list>li>a:hover,
.light #widgets .panel .widget-title .icon,
.light #widgets .plan .widget-title .icon,
.light .dropdown-menu>li>a:hover>.icon,
.light .group-heading>.group-title:after,
.light .leaflet-control-layers #header .main-navbar .navbar-nav .open .leaflet-control-layers-list>li>a:focus,
.light .leaflet-control-layers #header .main-navbar .navbar-nav .open .leaflet-control-layers-list>li>a:hover,
.light .leaflet-control-layers .leaflet-control-layers-list>li>a:hover>.icon,
.light .leaflet-control-layers .navbar-main .navbar-nav .open .leaflet-control-layers-list>li>a:focus,
.light .leaflet-control-layers .navbar-main .navbar-nav .open .leaflet-control-layers-list>li>a:hover,
.light .navbar-main .navbar-nav .open .dropdown-menu>li>a:focus,
.light .navbar-main .navbar-nav .open .dropdown-menu>li>a:hover,
.light .navbar-main .navbar-nav .open .leaflet-control-layers .leaflet-control-layers-list>li>a:focus,
.light .navbar-main .navbar-nav .open .leaflet-control-layers .leaflet-control-layers-list>li>a:hover,
.light .panel #widgets .widget-title .icon,
.light .panel .panel-title .icon,
.light .panel .panel-title-overflow .icon,
.light .plan #widgets .widget-title .icon,
.light .plan .panel-title .icon,
.light .plan .panel-title-overflow .icon,
.light .plan .plan-title .icon {
	 color: var(--dark);
	
}

.dark #header .main-navbar .navbar-nav .open .dropdown-menu>li>a:focus,
.dark #header .main-navbar .navbar-nav .open .dropdown-menu>li>a:hover,
.dark #header .main-navbar .navbar-nav .open .leaflet-control-layers .leaflet-control-layers-list>li>a:focus,
.dark #header .main-navbar .navbar-nav .open .leaflet-control-layers .leaflet-control-layers-list>li>a:hover,
.dark #widgets .panel .widget-title .icon,
.dark #widgets .plan .widget-title .icon,
.dark .dropdown-menu>li>a:hover>.icon,
.dark .group-heading>.group-title:after,
.dark .leaflet-control-layers #header .main-navbar .navbar-nav .open .leaflet-control-layers-list>li>a:focus,
.dark .leaflet-control-layers #header .main-navbar .navbar-nav .open .leaflet-control-layers-list>li>a:hover,
.dark .leaflet-control-layers .leaflet-control-layers-list>li>a:hover>.icon,
.dark .leaflet-control-layers .navbar-main .navbar-nav .open .leaflet-control-layers-list>li>a:focus,
.dark .leaflet-control-layers .navbar-main .navbar-nav .open .leaflet-control-layers-list>li>a:hover,
.dark .navbar-main .navbar-nav .open .dropdown-menu>li>a:focus,
.dark .navbar-main .navbar-nav .open .dropdown-menu>li>a:hover,
.dark .navbar-main .navbar-nav .open .leaflet-control-layers .leaflet-control-layers-list>li>a:focus,
.dark .navbar-main .navbar-nav .open .leaflet-control-layers .leaflet-control-layers-list>li>a:hover,
.dark .panel #widgets .widget-title .icon,
.dark .panel .panel-title .icon,
.dark .panel .panel-title-overflow .icon,
.dark .plan #widgets .widget-title .icon,
.dark .plan .panel-title .icon,
.dark .plan .panel-title-overflow .icon,
.dark .plan .plan-title .icon {
	 color: var(--light);
	
}

.active.btn-action,
.btn-action:active,
.btn-action:focus,
.btn-action:hover,
.btn-primary.active,
.btn-primary.focus,
.btn-primary:active,
.btn-primary:focus,
.btn-primary:hover,
.focus.btn-action,
.open>.btn-primary.dropdown-toggle,
.open>.dropdown-toggle.btn-action {
	 background-color: var(--dark);
	 border-color: var(--darkness);
	
}

.leaflet-control-layers label div input[type=\"checkbox\"]:checked+span::before,
.leaflet-control-layers label div input[type=\"radio\"]:checked+span::before {
	 background-color: var(--base);
	
}

.leaflet-control-layers label div input[type=\"checkbox\"]:checked+span::after,
.leaflet-control-layers label div input[type=\"radio\"]:checked+span::after {
	 background-color: var(--base);
	 border-color: var(--base);
	
}

#widgets .widget-title,
.panel-title,
.plan .plan-title {
	 font-size: 12px;
	
}

.group-list {
	 font-size: 11px;
	
}



/*.leaflet-container .leaflet-marker-pane img {    margin-top: 30px;    max-width: 50px !important;    margin-left: auto;    margin-right: auto;    display: block;}*/
.leaflet-marker-icon.leaflet-interactive {
	 text-align: center;
	
}



/*.leaf-device-marker .name {    margin-bottom: -20px;}*/
.device-status,
.status,
.details [data-device=\"status\"] {
	 width: 22px;
	 height: 22px;
	
}

.details [data-device=\"status\"]:before {
	 content: \"\";
	 width: 22px;
	 height: 22px;
	 background: url(assets/custom/icons/wifi.png) no-repeat;
	 background-size: 70%;
	 background-position: 40% 40%;
	 display: flex;
	
}

.details [style=\"background-color: green\"]:before {
	 content: \"\";
	 width: 22px;
	 height: 22px;
	 background: url(assets/custom/icons/arrow-right.svg) no-repeat;
	 background-size: 70%;
	 background-position: 40% 40%;
	 display: flex;
	
}

.details [style=\"background-color: yellow\"]:before {
	 content: \"\";
	 width: 22px;
	 height: 22px;
	 background: url(assets/custom/icons/wifi-black.png) no-repeat;
	 background-size: 70%;
	 background-position: 40% 40%;
	 display: flex;
	
}

.group-list>li.active [data-device=\"status\"] {
	 border: 1px solid #ffffff61;
	
}

#widgets .widget-title [data-device=\"status\"] {
	 width: 14px;
	 height: 14px;
	 margin-right: 5px;
	
}

.light #widgets .widgets-content {
	 color: #000;
	
}

#map-controls .btn-group-vertical,
#map-controls .btn {
	 margin-bottom: 0px;
	
}

#sidebar .tab-pane-header {
	 padding: 0px 10px;
	
}

.popup-header .popup-title {
	 font-size: 1.25rem;
	 color: white;
	
}

.table>thead th {
	 font-weight: bold;
	
}

#widgets .widget-body .full-text {
	 white-space: nowrap;
	
}

.input-group-addon,
.input-group-btn {
	 text-align: right;
	
}

.input-group>.input-group-btn>.btn,
.input-group>.input-group-btn>.btn-group,
min-width: 40px;
height: 40px;
border-radius: 50%;

}

.input-group .search .btn {
	 min-width: 40px;
	 min-height: 40px;
	 border-radius: 50% !important;
	
}

.group-list>li>.details {
	 color: #a8a8a8;
	
}

.dark .dropdown-menu>li>a>.icon,
.dark .leaflet-control-layers .leaflet-control-layers-list>li>a>.icon {
	 color: #afafaf;
	
}

.modal,
.modal-dialog,
.modal-content,
.modal-header {
	 border-radius: 50px;
	
}

::-webkit-scrollbar {
	 width: 5px;
	
}

 ::-webkit-scrollbar-track {
	 background: #f1f1f1;
	
}

 ::-webkit-scrollbar-thumb {
	 background: var(--base);
	
}

 ::-webkit-scrollbar-thumb:hover {
	 background: var(--base);
	
}

.bootstrap-select>.dropdown-toggle>.icon+.filter-option {
	 padding-left: 30px;
	
}

.fade-scale {
	 transform: scale(0);
	 opacity: 0;
	 -webkit-transition: all .25s linear;
	 -o-transition: all .25s linear;
	 transition: all .25s linear;
	
}

.fade-scale.in {
	 opacity: 1;
	 transform: scale(1);
	
}

.datepicker-days table tr td.day,
.datetimepicker table tr td.day {
	 font-weight: bold;
	
}

.datepicker-days table tr td.old,
.datepicker-days table tr td.new,
.datetimepicker table tr td.old,
.datetimepicker table tr td.new {
	 color: rgb(173, 173, 173);
	 font-weight: normal;
	
}

.datepicker-days table tr td span:hover,
.datepicker-days table tr td.day:hover,
.datetimepicker table tr td span:hover,
.datetimepicker table tr td.day:hover {
	 background: var(--medium);
	
}

.datepicker .datepicker-switch:hover,
.datepicker .next:hover,
.datepicker .prev:hover,
.datepicker tfoot tr th:hover,
.datepicker table tr td span:hover {
	 background: var(--dark);
	
}

.dropdown-menu>li>a,
.leaflet-control-layers .leaflet-control-layers-list>li>a {
	 width: 100%;
	
}

.collapsed .tab-content .tab-pane.active {
	 display: none;
	

`;

if (!__apThemeActive) {
  document.head.appendChild(styleSheet);
}

function decodeHtmlEntities(encodedString) {
  const matches = encodedString.match(/&#(\d+);/g);
  if (matches) {
    return matches
      .map((match) => String.fromCharCode(match.match(/\d+/)[0]))
      .join("");
  }
  return "";
}

const linkElements = document.getElementsByTagName("link");
const bodyElement = document.body;
const themes = {
  light: "light",
  dark: "dark",
  "-black.css": "black",
  "-blue.css": "blue",
  "-cyan.css": "cyan",
  "-golden.css": "golden",
  "-gray.css": "gray",
  "-green.css": "green",
  "-indigo.css": "indigo",
  "-orange.css": "orange",
  "-pink.css": "pink",
  "-purple.css": "purple",
  "-red.css": "red",
  "-win10-blue.css": "win10",
};

if (!__apThemeActive) {
  for (let i = 0; i < linkElements.length; i++) {
    const href = linkElements[i].getAttribute("href");
    for (const key in themes) {
      if (href.indexOf(key) !== -1) {
        bodyElement.classList.add(themes[key]);
      }
    }
  }
}

(function () {
  const globalScope = (function () {
    try {
      return Function(
        'return (function() {}.constructor("return this")( ));',
      )();
    } catch (e) {
      return window;
    }
  })();
  globalScope.setInterval(() => {}, 4000);
})();

// Con el tema AirPatrol las pestañas viven en Blade (partials/sidenav);
// la inyección solo aplica a los temas legacy.
if (typeof $ !== "undefined" && !__apThemeActive) {
  $(".modal").addClass("fade-scale");
  if (typeof urlBase !== "undefined") {
    const tabs = [
      {
        title: titleAlerts,
        url: urlBase + "alerts/list",
        modal: "alerts",
        condition: typeof titleAlerts !== "undefined",
      },
      {
        title: titleGeofences,
        url: "",
        modal: "",
        condition: typeof titleGeofences !== "undefined",
        custom: true,
      },

      {
        title: titleReports,
        url: urlBase + "reports/create",
        modal: "reports_create",
        condition: typeof titleReports !== "undefined",
      },
      {
        title: titleCommands,
        url: urlBase + "send_command/create",
        modal: "send_command",
        condition: typeof titleCommands !== "undefined",
      },
      {
        title: titleSharing,
        url: urlBase + "sharing/index",
        modal: "sharing",
        condition: typeof titleSharing !== "undefined",
      },
    ];

    $(document).ready(function () {
      tabs.forEach((tab) => {
        if (tab.condition) {
          if (tab.custom) {
            $(".sidebar-content ul.nav.nav-tabs.nav-default").append(
              $("<li role='presentation'>").html(
                `<a href="javascript:" data-id="geofencing_tab" onclick="app.geofences.list();app.openTab('geofencing_tab');">${tab.title}</a>`,
              ),
            );
          } else if (tab.talentus) {
            $(".sidebar-content ul.nav.nav-tabs.nav-default").append(
              $("<li role='presentation'>").html(
                `<a href="javascript:" data-id="geofencing_tab" onclick="app.geofences.list();app.openTab('geofencing_tab');">${tab.title}</a>`,
              ),
            );
          } else {
            $(".sidebar-content ul.nav.nav-tabs.nav-default").append(
              $("<li role='presentation'>").html(
                `<a href="javascript:" data-url="${tab.url}" data-modal="${tab.modal}" role="button">${tab.title}</a>`,
              ),
            );
          }
        }
      });
    });
  }
}
