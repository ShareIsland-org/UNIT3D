<?php

declare(strict_types=1);

/**
 * NOTICE OF LICENSE.
 *
 * UNIT3D Community Edition is open-sourced software licensed under the GNU Affero General Public License v3.0
 * The details is bundled with this project in the file LICENSE.txt.
 *
 * @project    UNIT3D Community Edition
 *
 * @license    https://www.gnu.org/licenses/agpl-3.0.en.html/ GNU Affero General Public License v3.0
 */

use App\Models\Category;
use App\Models\User;

/*
 * The upload and request forms prefill external ids from the query string into Alpine expressions
 * (x-bind:value="... ? '<value>' : ''"). Blade's {{ }} turns an apostrophe into &#039;, which the browser decodes
 * back into a real quote before Alpine evaluates the attribute, so a crafted link could run script in the page.
 * The values must be written with Js::from(), which encodes the apostrophe as '.
 */

dataset('prefill parameters', ['tmdb_movie_id', 'tmdb_tv_id', 'imdb', 'tvdb', 'mal', 'igdb']);

// The decoded attribute would contain a closing quote followed by script.
const CREATE_VIEWS_XSS_PAYLOAD = "a' + alert(1) + 'b";

test('the request form does not let a query parameter break out of the alpine expression', function (string $parameter): void {
    Category::factory()->create();
    $user = User::factory()->create(['can_request' => true]);

    $response = $this->actingAs($user)
        ->get(route('requests.create', [$parameter => CREATE_VIEWS_XSS_PAYLOAD]))
        ->assertOk();

    $response->assertDontSee("&#039; + alert(1) + &#039;", false);
    $response->assertSee("'a\\u0027 + alert(1) + \\u0027b'", false);
})->with('prefill parameters');

test('the upload form does not let a query parameter break out of the alpine expression', function (string $parameter): void {
    Category::factory()->create();
    $user = User::factory()->create(['can_upload' => true]);

    $response = $this->actingAs($user)
        ->get(route('torrents.create', [$parameter => CREATE_VIEWS_XSS_PAYLOAD]))
        ->assertOk();

    $response->assertDontSee("&#039; + alert(1) + &#039;", false);
    $response->assertSee("'a\\u0027 + alert(1) + \\u0027b'", false);
})->with('prefill parameters');

test('the request form still prefills a plain id', function (): void {
    Category::factory()->create();
    $user = User::factory()->create(['can_request' => true]);

    $this->actingAs($user)
        ->get(route('requests.create', ['tmdb_movie_id' => '12345']))
        ->assertOk()
        ->assertSee("'12345'", false);
});

test('the upload form still prefills a plain id', function (): void {
    Category::factory()->create();
    $user = User::factory()->create(['can_upload' => true]);

    $this->actingAs($user)
        ->get(route('torrents.create', ['tmdb_movie_id' => '12345']))
        ->assertOk()
        ->assertSee("'12345'", false);
});
