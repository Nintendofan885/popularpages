<?php

/**
 * This file is part of Popularpages application
 * Copyright (C) 2016  Niharika Kohli and contributors
 *
 * This program is free software: you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the Free
 * Software Foundation, either version 3 of the License, or (at your option)
 * any later version.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for
 * more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Niharika Kohli <nkohli@wikimedia.org>
 * @copyright © 2016 Niharika Kohli and contributors.
 */

class GetConfig {

	public function getJSONConfig( $page ) {
		$api = new ApiHelper();
		$params = [
			'page' => $page,
			'prop' => 'wikitext'
		];
		$res = $api->apiQuery( $params, 'parse' );
		$res = json_decode( $res['parse']['wikitext'], true );
		$config = [];
		foreach ( $res as $k => $v ) {
			if ( $k === 'description' ) {
				continue;
			}
			$config[$k] = [
				'Report' => $v['Report'],
				'Limit' => $v['Limit'],
				'Name' => $v['Name']
			];
		}
		return $config;
	}
}
