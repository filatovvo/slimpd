<?php
namespace Slimpd\Traits;
/* Copyright (C) 2016 othmar52 <othmar52@users.noreply.github.com>
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
use Psr\Http\Message\ResponseInterface as Response;

trait CommonResponseMethods {

    public function redirectToSignIn(Response $response) {
        return $response->withRedirect(
            $this->router->pathFor('auth.signin') .
            getNoSurSuffix($this->view->getEnvironment()->getGlobals()['nosurrounding'])
        );
    }

    public function renderAccessDenied(Response $response) {
        $this->view->render($response, 'error/401.htm');
        return $response->withStatus(401);
    }
}
