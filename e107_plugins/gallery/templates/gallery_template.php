<?php
/*
* Copyright (c) 2012 e107 Inc e107.org, Licensed under GNU GPL (http://www.gnu.org/licenses/gpl.txt)
* $Id: e_shortcode.php 12438 2011-12-05 15:12:56Z secretr $
*
* Gallery Template 
*/

  
$GALLERY_TEMPLATE['LIST_START'] = 
	"<div class='gallery-list-start'>";

		
$GALLERY_TEMPLATE['LIST_ITEM'] = 
	"<div class='gallery-list-item'>
	<div>{GALLERY_THUMB}</div>
	<div class='gallery-list-caption'>{GALLERY_CAPTION}</div>
	</div>
	";

$GALLERY_TEMPLATE['LIST_END'] = 
	"</div>
	<div class='gallery-list-end' >
	<div class='gallery-list-nextprev'>{GALLERY_NEXTPREV}</div>
	<div class='gallery-list-back'><a href='".e_SELF."'>Back to Categories</a></div>
	</div>
	";
	
	
$GALLERY_TEMPLATE['CAT_START'] = 
	"<div class='gallery-cat-start'>";

		
$GALLERY_TEMPLATE['CAT_ITEM'] = 
	"<div class='gallery-cat-item'>
	<div class='gallery-cat-thumb'>{GALLERY_CAT_THUMB}</div>
	<div class='gallery-cat-title'><h3>{GALLERY_CAT_TITLE}</h3></div>
	</div>
	";

$GALLERY_TEMPLATE['CAT_END'] = 
	"</div>
	<div class='gallery-cat-end'>
	</div>
	";

// {GALLERY_SLIDESHOW=X}  X = Gallery Category. Default: 1 (ie. 'gallery_1')
// {GALLERY_SLIDES=X}  X = number of items per slide. 
$GALLERY_TEMPLATE['SLIDESHOW_WRAPPER'] = '
			<div id="carousel-wrapper">
			    <div id="carousel-content">
			        {GALLERY_SLIDES=3}
			    </div>
			</div>
			<div class="controls">
            	<a href="javascript:" class="carousel-control ico-next" rel="next" style="float: right">Next &rsaquo;</a>
                <a href="javascript:" class="carousel-control ico-prev" rel="prev">&lsaquo; Previous</a>
            </div>
		';	

$GALLERY_TEMPLATE['SLIDESHOW_SLIDE_ITEM'] = '<span class="gallery-slide-item">{GALLERY_THUMB}</span>';

		

?>