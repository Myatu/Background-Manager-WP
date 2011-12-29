<?php

/*
 * Copyright (c) 2011 Mike Green <myatus@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Myatu\WordPress\BackgroundManager\Importers;

use Myatu\WordPress\BackgroundManager\Main;
use Myatu\WordPress\BackgroundManager\Common\FlickrApi;
use Myatu\WordPress\BackgroundManager\Galleries;
use Myatu\WordPress\BackgroundManager\Images;

/**
 * Importer for Flickr
 *
 * This product uses the Flickr API but is not endorsed or certified by Flickr.
 * 
 * @author Mike Green <myatus@gmail.com>
 * @package BackgroundManager
 * @subpackage Importers
 */
class Flickr extends Importer
{
    const NAME = 'Flickr Photo Sets';
    const DESC = 'Imports photo sets at from Flickr. This product uses the Flickr API but is not endorsed or certified by Flickr.';
       
    /**
     * Pre-import settings
     *
     * Allows the selection of a particular gallery
     */
    static public function preImport(Main $main)
    {
        // A photoset has been selected
        if (isset($_REQUEST['flickr_photoset']) && !empty($_REQUEST['flickr_photoset']))
            return;
        
        $flickr = new FlickrApi($main);
        $vars   = array();
        $tokens = false;
        
        // Callback URL
        $importer     = is_array($class = explode('\\', get_class())) ? $class[count($class)-1] : 'Flickr';
        $callback_url = add_query_arg(array(
            'importer' => $importer,
            '_nonce'   => wp_create_nonce('onImportMenu'),
        ));
        
        // Logout URL
        $vars['logout_url'] = add_query_arg(array(
            'logout' => true,
        ), $callback_url);
       
        // Perform logout, if requested
        if (isset($_REQUEST['logout']))
            $flickr->deleteAccessTokens();            
        
        // If we do not have valid access tokens, ask the user what to do.
        if (!$flickr->hasValidAccessTokens()) {
            if (!isset($_REQUEST['do_login'])) {
                // We have not been authorized to access Flickr, except public side. Ask the user what to do.
                $vars['ask_auth'] = true;
            } else if ($do_login = ($_REQUEST['do_login'] == 'yes')) {
                // User has decided to login to Flickr
                
                $url = $flickr->getAuthorizeUrl(get_site_url(null, '', 'admin') . $callback_url);
                
                if ($url) {
                    $vars['auth_redir'] = $url;
                } else {
                    // Something went wrong, repeat the process (asking what the user wants to do)
                    $vars['errors'] = __('Unable to obtain a Flickr authorization URL. Please try again later.', $main->getName());
                    $vars['ask_auth'] = true;
                }
            } else {
                // User has decided to continue anonymously, clear any invalid tokens if present
                $flickr->deleteAccessTokens();
            }
        } else {
            // Valid access tokens, we grab the tokens, which contains a username
            $tokens = $flickr->getAccessTokens();
            
            if ($tokens)
                $vars['username'] = $tokens['username'];
        }
        
        // Set anonymous flag
        $vars['anonymous'] = ($tokens === false);
        
        // Set a username from whom we obtain the photoset
        $flickr_username =  (isset($_REQUEST['flickr_username'])) ? $_REQUEST['flickr_username'] : '';
        $vars['flickr_username'] = $flickr_username;
        
        // If we have a username specified, or are using our authorized tokens...
        if ($tokens || $flickr_username) {
            $photoset_list = false;
            
            if ($tokens && (empty($flickr_username) || strcasecmp($flickr_username, $tokens['username']) == 0)) {
                // Obtain photoset list from authenticated user
                $photoset_list = $flickr->call('photosets.getList');
            } else {
                // Obtain photoset list from another user
                $flickr_id = false;
                
                if (!strpos($flickr_username, '@')) { // @ cannot be the first match, so safe to use a "!"
                    // Obtain NSID by Username
                    $flickr_id_result = $flickr->call('people.findByUsername', array('username' => $flickr_username));
                    
                    if ($flickr->isValid($flickr_id_result) && isset($flickr_id_result['user']['nsid']))
                        $flickr_id = $flickr_id_result['user']['nsid'];
                } else {
                    // Obtain NSID by ID
                    $flickr_id_result = $flickr->call('people.getInfo', array('user_id' => $flickr_username));

                    if ($flickr->isValid($flickr_id_result) && isset($flickr_id_result['person']['nsid']))
                        $flickr_id = $flickr_id_result['person']['nsid']; // This is redundant, I know
                }
                
                if ($flickr_id) {
                    $photoset_list = $flickr->call('photosets.getList', array('user_id' => $flickr_id));
                } else {
                    $vars['errors'] = sprintf(__('"%s" is not a valid Flickr user.', $main->getName()), $flickr_username);
                }
            }
            
            if ($flickr->isValid($photoset_list) && isset($photoset_list['photosets'])) {
                // Flickr reserves the option to return paginated results.
                
                if (isset($photoset_list['photosets']['photoset']) && is_array($photoset_list['photosets']['photoset']))
                    foreach($photoset_list['photosets']['photoset'] as $photoset)
                        $vars['photosets'][$photoset['id']] = sprintf('%s (%d)', $photoset['title']['_content'], $photoset['photos']);
                
                // Sort the array
                if (isset($vars['photosets']))
                    asort($vars['photosets']);
            }
        }
        
        return $main->template->render('importer_flickr.html.twig', $vars);
    }
       
    /**
     * Performs the import from Flickr
     *
     * @param object $main The object of the Main class
     */
    static public function doImport(Main $main)
    {
        // Just in case
        if (!isset($_REQUEST['flickr_photoset']) || empty($_REQUEST['flickr_photoset']))
            return;
            
        $galleries = new Galleries($main);
        $images    = new Images($main);            
        $flickr    = new FlickrApi($main);        
        
        // Create local Image Set
        if ($flickr->isValid($photoset_info = $flickr->call('photosets.getInfo', array('photoset_id' => $_REQUEST['flickr_photoset'])))) {
            $image_set  = sprintf(__('%s (Imported)', $main->getName()), $photoset_info['photoset']['title']['_content']);
            $gallery_id = $galleries->save(0, $image_set, $photoset_info['photoset']['description']['_content']);
            
            if (!$gallery_id) {
                $main->addDelayedNotice(sprintf(__('Unable to create Image Set <strong>%s</strong>', $main->getName()), $image_set), true);
                return;
            }               
        } else {
            $main->addDelayedNotice(__('Invalid or inaccessible Flickr Photo Set selected', $main->getName()), true);
            return;
        }
        
        
        $page      = 1;
        $pb_chunk  = 0;
        $failed    = 0;
        
        // Iterate photos on Flickr
        while ($flickr->isValid($photoset = $flickr->call('photosets.getPhotos', array('photoset_id' => $_REQUEST['flickr_photoset'], 'media' => 'photos', 'page' => $page))) && isset($photoset['photoset'])) {
            $photoset  = $photoset['photoset'];
            $pages     = $photoset['pages'];
            $total     = $photoset['total'];
            $pb_chunks = ceil(100 / $total -1); // For progress bar
            
            // Iterate each photo in current 'page'
            foreach ($photoset['photo'] as $photo) {
                $image_url    = '';
                $description  = '';
                $title        = $photo['title'];
                $can_download = true;
                
                // Attempt to obtain additional information about the photo
                if ($flickr->isValid($info = $flickr->call('photos.getInfo', array('photo_id' => $photo['id'], 'secret' => $photo['secret']))) && isset($info['photo'])) {
                    $info = $info['photo'];
                    
                    $description  = $info['description']['_content'];
                    $can_download = ($info['usage']['candownload'] == 1);
                }
                
                // Select the largest size available to us
                if ($can_download && $flickr->isValid($sizes = $flickr->call('photos.getSizes', array('photo_id' => $photo['id'])))) {
                    $current_w = 0;
                    $current_h = 0;
                    
                    foreach($sizes['sizes']['size'] as $size) {
                        if ($size['width'] > $current_w || $size['height'] > $current_h) {
                            $image_url = $size['source'];
                            $current_w = $size['width'];
                            $current_h = $size['height'];
                        }
                    }
                }
                
                // If we have an URL, download it and insert the photo into the local Image Set
                if (!empty($image_url)) {
                    $tmp = download_url($image_url);
                    
                    if (!is_wp_error($tmp)) {
                        $id = media_handle_sideload(array('name' => basename($image_url), 'tmp_name' => $tmp), $gallery_id, null, array('post_title' => $title, 'post_content' => $description));
                        
                        if (is_wp_error($id))
                            $failed++; // Failed to sideload media
                    } else {
                        $failed++; // Failed to download media
                    }
                    
                    @unlink($tmp); // Remove temporary file if it still exists
                }
                
                // Update progress bar
                $pb_chunk++;
                static::setProgress($pb_chunk * $pb_chunks);
            }
            
            // Go to next page of photos on Flickr
            if ($page < $pages) {
                $page++;
            } else {
                break;
            }
        }
        
        if ($failed > 0)
            $main->addDelayedNotice(sprintf(__('%d photos could not be added.', $main->getName()), $failed), true);
    }
}