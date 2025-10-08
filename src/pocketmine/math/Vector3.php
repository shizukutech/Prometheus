<?php

/*
 *
 *  ____            _        _   __  __ _                  __  __ ____
 * |  _ \ ___   ___| | _____| |_|  \/  (_)_ __   ___      |  \/  |  _ \
 * | |_) / _ \ / __| |/ / _ \ __| |\/| | | '_ \ / _ \_____| |\/| | |_) |
 * |  __/ (_) | (__|   <  __/ |_| |  | | | | | |  __/_____| |  | |  __/
 * |_|   \___/ \___|_|\_\___|\__|_|  |_|_|_| |_|\___|     |_|  |_|_|
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author PocketMine Team
 * @link http://www.pocketmine.net/
 *
 *
*/

namespace pocketmine\math;

class Vector3{

	const SIDE_DOWN = 0;
	const SIDE_UP = 1;
	const SIDE_NORTH = 2;
	const SIDE_SOUTH = 3;
	const SIDE_WEST = 4;
	const SIDE_EAST = 5;

	public float|int $x;
	public float|int $y;
	public float|int $z;

	public function __construct(float|int $x = 0, float|int $y = 0, float|int $z = 0){
		$this->x = $x;
		$this->y = $y;
		$this->z = $z;
	}

	public function getX() : float|int{
		return $this->x;
	}

	public function getY() : float|int{
		return $this->y;
	}

	public function getZ() : float|int{
		return $this->z;
	}

	public function getFloorX() : int{
		return (int) floor($this->x);
	}

	public function getFloorY() : int{
		return (int) floor($this->y);
	}

	public function getFloorZ() : int{
		return (int) floor($this->z);
	}

	public function getRight() : float|int{
		return $this->x;
	}

	public function getUp() : float|int{
		return $this->y;
	}

	public function getForward() : float|int{
		return $this->z;
	}

	public function getSouth() : float|int{
		return $this->x;
	}

	public function getWest() : float|int{
		return $this->z;
	}

	public function add(float|int|Vector3 $x, float|int $y = 0, float|int $z = 0) : Vector3{
		if($x instanceof Vector3){
			return new Vector3($this->x + $x->x, $this->y + $x->y, $this->z + $x->z);
		}else{
			return new Vector3($this->x + $x, $this->y + $y, $this->z + $z);
		}
	}

	public function subtract(float|int|Vector3 $x = 0, float|int $y = 0, float|int $z = 0) : Vector3{
		if($x instanceof Vector3){
			return $this->add(-$x->x, -$x->y, -$x->z);
		}else{
			return $this->add(-$x, -$y, -$z);
		}
	}

	public function multiply(float $number) : Vector3{
		return new Vector3($this->x * $number, $this->y * $number, $this->z * $number);
	}

	public function divide(float $number) : Vector3{
		return new Vector3($this->x / $number, $this->y / $number, $this->z / $number);
	}

	public function ceil() : Vector3{
		return new Vector3((int) ceil($this->x), (int) ceil($this->y), (int) ceil($this->z));
	}

	public function floor() : Vector3{
		return new Vector3((int) floor($this->x), (int) floor($this->y), (int) floor($this->z));
	}

	public function round() : Vector3{
		return new Vector3((int) round($this->x), (int) round($this->y), (int) round($this->z));
	}

	public function abs() : Vector3{
		return new Vector3(abs($this->x), abs($this->y), abs($this->z));
	}

	public function getSide(int $side, int $step = 1) : Vector3{
		switch($side){
			case Vector3::SIDE_DOWN:
				return new Vector3($this->x, $this->y - $step, $this->z);
			case Vector3::SIDE_UP:
				return new Vector3($this->x, $this->y + $step, $this->z);
			case Vector3::SIDE_NORTH:
				return new Vector3($this->x, $this->y, $this->z - $step);
			case Vector3::SIDE_SOUTH:
				return new Vector3($this->x, $this->y, $this->z + $step);
			case Vector3::SIDE_WEST:
				return new Vector3($this->x - $step, $this->y, $this->z);
			case Vector3::SIDE_EAST:
				return new Vector3($this->x + $step, $this->y, $this->z);
			default:
				return $this;
		}
	}

	public static function getOppositeSide(int $side) : int{
		switch($side){
			case Vector3::SIDE_DOWN:
				return Vector3::SIDE_UP;
			case Vector3::SIDE_UP:
				return Vector3::SIDE_DOWN;
			case Vector3::SIDE_NORTH:
				return Vector3::SIDE_SOUTH;
			case Vector3::SIDE_SOUTH:
				return Vector3::SIDE_NORTH;
			case Vector3::SIDE_WEST:
				return Vector3::SIDE_EAST;
			case Vector3::SIDE_EAST:
				return Vector3::SIDE_WEST;
			default:
				return -1;
		}
	}

	public function distance(Vector3 $pos) : float{
		return sqrt($this->distanceSquared($pos));
	}

	public function distanceSquared(Vector3 $pos) : float{
		return pow($this->x - $pos->x, 2) + pow($this->y - $pos->y, 2) + pow($this->z - $pos->z, 2);
	}

	public function maxPlainDistance(float|Vector2|Vector3 $x = 0, float $z = 0) : float{
		if($x instanceof Vector3){
			return $this->maxPlainDistance($x->x, $x->z);
		}elseif($x instanceof Vector2){
			return $this->maxPlainDistance($x->x, $x->y);
		}else{
			return max(abs($this->x - $x), abs($this->z - $z));
		}
	}

	public function length() : float{
		return sqrt($this->lengthSquared());
	}

	public function lengthSquared() : float{
		return $this->x * $this->x + $this->y * $this->y + $this->z * $this->z;
	}

	public function normalize() : Vector3{
		$len = $this->lengthSquared();
		if($len > 0){
			return $this->divide(sqrt($len));
		}

		return new Vector3(0, 0, 0);
	}

	public function dot(Vector3 $v) : float{
		return $this->x * $v->x + $this->y * $v->y + $this->z * $v->z;
	}

	public function cross(Vector3 $v) : Vector3{
		return new Vector3(
			$this->y * $v->z - $this->z * $v->y,
			$this->z * $v->x - $this->x * $v->z,
			$this->x * $v->y - $this->y * $v->x
		);
	}

	public function equals(Vector3 $v) : bool{
		return $this->x == $v->x && $this->y == $v->y && $this->z == $v->z;
	}

	/**
	 * Returns a new vector with x value equal to the second parameter, along the line between this vector and the
	 * passed in vector, or null if not possible.
	 */
	public function getIntermediateWithXValue(Vector3 $v, float $x) : ?Vector3{
		$xDiff = $v->x - $this->x;
		$yDiff = $v->y - $this->y;
		$zDiff = $v->z - $this->z;

		if(($xDiff * $xDiff) < 0.0000001){
			return null;
		}

		$f = ($x - $this->x) / $xDiff;

		if($f < 0 || $f > 1){
			return null;
		}else{
			return new Vector3($this->x + $xDiff * $f, $this->y + $yDiff * $f, $this->z + $zDiff * $f);
		}
	}

	/**
	 * Returns a new vector with y value equal to the second parameter, along the line between this vector and the
	 * passed in vector, or null if not possible.
	 */
	public function getIntermediateWithYValue(Vector3 $v, float $y) : ?Vector3{
		$xDiff = $v->x - $this->x;
		$yDiff = $v->y - $this->y;
		$zDiff = $v->z - $this->z;

		if(($yDiff * $yDiff) < 0.0000001){
			return null;
		}

		$f = ($y - $this->y) / $yDiff;

		if($f < 0 || $f > 1){
			return null;
		}else{
			return new Vector3($this->x + $xDiff * $f, $this->y + $yDiff * $f, $this->z + $zDiff * $f);
		}
	}

	/**
	 * Returns a new vector with z value equal to the second parameter, along the line between this vector and the
	 * passed in vector, or null if not possible.
	 */
	public function getIntermediateWithZValue(Vector3 $v, float $z) : ?Vector3{
		$xDiff = $v->x - $this->x;
		$yDiff = $v->y - $this->y;
		$zDiff = $v->z - $this->z;

		if(($zDiff * $zDiff) < 0.0000001){
			return null;
		}

		$f = ($z - $this->z) / $zDiff;

		if($f < 0 || $f > 1){
			return null;
		}else{
			return new Vector3($this->x + $xDiff * $f, $this->y + $yDiff * $f, $this->z + $zDiff * $f);
		}
	}

	public function setComponents(float|int $x, float|int $y, float|int $z) : Vector3{
		$this->x = $x;
		$this->y = $y;
		$this->z = $z;
		return $this;
	}

	public function __toString() : string{
		return "Vector3(x=" . $this->x . ",y=" . $this->y . ",z=" . $this->z . ")";
	}

}
