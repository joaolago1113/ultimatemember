<style type="text/css">
<?php

$photosize = str_replace('px','',$photosize);
$photosize_up = ( $photosize / 2 ) + 10;
$meta_padding = ( $photosize + 60 ) . 'px';

if ( $photocorner == 1 ) {
print ".um-$form_id.um .um-profile-photo a.um-profile-photo-img, .um-profile-photo img { border-radius: 999px !important }";
}

if ( $photocorner == 2 ) {
print ".um-$form_id.um .um-profile-photo a.um-profile-photo-img, .um-profile-photo img { border-radius: 4px !important }";
}

if ( $photocorner == 3 ) {
print ".um-$form_id.um .um-profile-photo a.um-profile-photo-img, .um-profile-photo img { border-radius: 0px !important }";
}

print "
.um-$form_id.um .um-header.no-cover a.um-profile-photo-img img,
.um-$form_id.um .um-profile-photo a.um-profile-photo-img {
	width: ".$photosize."px;
	height: ".$photosize."px;
}
";

print "
.um-$form_id.um .um-profile-photo a.um-profile-photo-img {
	top: -".$photosize_up."px;
}
";

print "
.um-$form_id.um .um-profile-meta {
	padding-left: $meta_padding;
}
";

if ( $main_bg ) {
print ".um-$form_id.um-profile {
	background-color: $main_bg !important;
}";
}

if ( $header_bg ) {
print ".um-$form_id.um .um-header {
	background-color: $header_bg;
}";
}

if ( $header_text ) {
print ".um-$form_id.um .um-profile-meta {
	color: $header_text;
}";
}

if ( $header_link_color ) {
print "
.um-$form_id.um .um-name a {
	color: $header_link_color;
}
";
}

if ( $header_link_hcolor ) {
print "
.um-$form_id.um .um-name a:hover {
	color: $header_link_hcolor;
}
";
}

if ( $header_icon_color ) {
print "
.um-$form_id.um .um-profile-headericon a {
	color: $header_icon_color;
}
";
}

if ( $header_icon_hcolor ) {
print "
.um-$form_id.um .um-profile-headericon a:hover {
	color: $header_icon_hcolor;
}
";
}

?>
</style>