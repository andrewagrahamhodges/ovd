/**
* ===========================================
* Java Pdf Extraction Decoding Access Library
* ===========================================
*
* Project Info:  http://www.jpedal.org
* (C) Copyright 1997-2008, IDRsolutions and Contributors.
*
* 	This file is part of JPedal
*
    This library is free software; you can redistribute it and/or
    modify it under the terms of the GNU General Public
    License as published by the Free Software Foundation; either
    version 2.1 of the License, or (at your option) any later version.

    This library is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
    General Public License for more details.

    You should have received a copy of the GNU General Public
    License along with this library; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA


*
* ---------------
* JPedalSettings.java
* ---------------
*/
package org.jpedal.constants;

/**
 * used by JPedal for contants which allow user to set flags
 */
public class JPedalSettings {
    
	/**allow user to set colour used to highlight text found in JPanel*/
	public static final Integer TEXT_HIGHLIGHT_COLOUR = new Integer(1);
    
	/**allow user to display invisible text in current fill colour*/
	public static final Integer DISPLAY_INVISIBLE_TEXT = new Integer(2);

    /**allow user to cache large fonts to avoid big memory hit*/
    public static final Integer CACHE_LARGE_FONTS=new Integer(3);

    /**allow user print all fonts as textprinting*/
    public static final Integer TEXT_PRINT_NON_EMBEDDED_FONTS=new Integer(4);

    /**allow user to define color for text when highlighted*/
    public static final Integer TEXT_INVERTED_COLOUR =new Integer(5);
    
    /**allow user to define custom upscaling val to improve quality of extr images*/
    public static final Integer IMAGE_UPSCALE =new Integer(6);
    
    /**allow user to set a falg to use hi res settings*/
    public static final Integer IMAGE_HIRES =new Integer(7);
    
    /**allow user to extract best quality images at the cost of memory */
    public static final Integer EXTRACT_AT_BEST_QUALITY = new Integer(8);

}
