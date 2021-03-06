<?php
namespace Slimpd\Modules\Bitmap;
/* Copyright (C) 2015-2016 othmar52 <othmar52@users.noreply.github.com>
 *
 * This file is part of sliMpd - a php based mpd web client
 *
 * This program is free software: you can redistribute it and/or modify it
 * under the terms of the GNU Affero General Public License as published by the
 * Free Software Foundation, either version 3 of the License, or (at your
 * option) any later version.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE. See the GNU Affero General Public License
 * for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
// from http://compareimages.nikhazy-dizajn.hu/
class CompareBitmaps {
    protected function mimeType($fileName) {
        /*returns array with mime type and if its jpg or png. Returns false if it isn't jpg or png*/
        $mime = getimagesize($fileName);
        $return = array($mime[0], $mime[1]);

        switch ($mime['mime']) {
            case 'image/jpeg':
                $return[] = 'jpg';
                return $return;
            case 'image/png':
                $return[] = 'png';
                return $return;
            default:
                return false;
        }
    }

    protected function createImage($fileName)
    {
        /*retuns image resource or false if its not jpg or png*/
        $mime = $this->mimeType($fileName);

        if($mime[2] === 'jpg') {
            return imagecreatefromjpeg($fileName);
        } 
        if ($mime[2] === 'png') {
            return imagecreatefrompng($fileName);
        } 
        return false;
    }

    protected function resizeImage($fileName) {
        /*resizes the image to a 8x8 squere and returns as image resource*/
        $mime = $this->mimeType($fileName);
        $img = imagecreatetruecolor(8, 8);
        $fileName = $this->createImage($fileName);
        imagecopyresized($img, $fileName, 0, 0, 0, 0, 8, 8, $mime[0], $mime[1]);
        return $img;
    }

    protected function colorMeanValue($fileName) {
        /*returns the mean value of the colors and the list of all pixel's colors*/
        $colorList = array();
        $colorSum = 0;
        for($horiz = 0;$horiz<8;$horiz++) {
            for($verti = 0;$verti<8;$verti++) {
                $rgb = imagecolorat($fileName, $horiz, $verti);
                $colorList[] = $rgb & 0xFF;
                $colorSum += $rgb & 0xFF;
            }
        }
        return array($colorSum/64,$colorList);
    }

    protected function bits($colorMean){
        /*returns an array with 1 and zeros. If a color is bigger than the mean value of colors it is 1*/
        $bits = array();
        foreach($colorMean[1] as $color){
            $bits[]= ($color>=$colorMean[0]) ? 1 : 0;
        }
        return $bits;
    }

    public function compare($fileNameA, $fileNameB) {
        /*main function. returns the hammering distance of two images' bit value*/
        $imageA = $this->createImage($fileNameA);
        $imageB = $this->createImage($fileNameB);

        if(!$imageA || !$imageB){
            return false;
        }

        $imageA = $this->resizeImage($imageA, $fileNameA);
        $imageB = $this->resizeImage($imageB, $fileNameB);

        imagefilter($imageA, IMG_FILTER_GRAYSCALE);
        imagefilter($imageB, IMG_FILTER_GRAYSCALE);

        $colorMean1 = $this->colorMeanValue($imageA);
        $colorMean2 = $this->colorMeanValue($imageB);

        $bits1 = $this->bits($colorMean1);
        $bits2 = $this->bits($colorMean2);

        $hammeringDistance = 0;
        for($num = 0; $num < 64; $num++) {
            if($bits1[$num] != $bits2[$num]) {
                $hammeringDistance++;
            }
        }
        return $hammeringDistance;
    }
}
