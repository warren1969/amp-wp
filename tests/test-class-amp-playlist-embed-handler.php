<?php
/**
 * Tests for AMP_Playlist_Embed_Handler.
 *
 * @package AMP
 * @since 0.7
 */

/**
 * Tests for AMP_Playlist_Embed_Handler.
 *
 * @package AMP
 * @covers AMP_Playlist_Embed_Handler
 */
class Test_AMP_Playlist_Embed_Handler extends WP_UnitTestCase {

	/**
	 * Instance of the tested class.
	 *
	 * @var AMP_Playlist_Embed_Handler.
	 */
	public $instance;

	/**
	 * The first tested file.
	 *
	 * @var string.
	 */
	public $file_1;

	/**
	 * The second tested file.
	 *
	 * @var string.
	 */
	public $file_2;

	/**
	 * Set up test.
	 */
	public function setUp() {
		parent::setUp();
		$this->instance = new AMP_Playlist_Embed_Handler();
	}

	/**
	 * Tear down test.
	 *
	 * @global WP_Styles $wp_styles
	 */
	public function tearDown() {
		global $wp_styles;
		$wp_styles = null;

		AMP_Playlist_Embed_Handler::$playlist_id = 0;
	}

	/**
	 * Test register_embed.
	 *
	 * @covers AMP_Playlist_Embed_Handler::register_embed()
	 */
	public function test_register_embed() {
		global $shortcode_tags;
		$removed_shortcode = 'wp_playlist_shortcode';
		add_shortcode( 'playlist', $removed_shortcode );
		$this->instance->register_embed();
		$this->assertEquals( 'AMP_Playlist_Embed_Handler', get_class( $shortcode_tags[ AMP_Playlist_Embed_Handler::SHORTCODE ][0] ) );
		$this->assertEquals( 'shortcode', $shortcode_tags[ AMP_Playlist_Embed_Handler::SHORTCODE ][1] );
		$this->assertEquals( $removed_shortcode, $this->instance->removed_shortcode_callback );
		$this->instance->unregister_embed();
	}

	/**
	 * Test unregister_embed.
	 *
	 * @covers AMP_Playlist_Embed_Handler::unregister_embed()
	 */
	public function test_unregister_embed() {
		global $shortcode_tags;
		$expected_removed_shortcode                 = 'wp_playlist_shortcode';
		$this->instance->removed_shortcode_callback = $expected_removed_shortcode;
		$this->instance->unregister_embed();
		$this->assertEquals( $expected_removed_shortcode, $shortcode_tags[ AMP_Playlist_Embed_Handler::SHORTCODE ] );
	}

	/**
	 * Test styling.
	 *
	 * @covers AMP_Playlist_Embed_Handler::enqueue_styles()
	 */
	public function test_styling() {
		global $post;
		$playlist_shortcode = 'amp-playlist-shortcode';
		$this->instance->register_embed();
		$this->assertFalse( in_array( 'wp-mediaelement', wp_styles()->queue, true ) );
		$this->assertFalse( in_array( $playlist_shortcode, wp_styles()->queue, true ) );

		$post               = $this->factory()->post->create_and_get();
		$post->post_content = '[playlist ids="5,3"]';
		$this->instance->enqueue_styles();
		$style = wp_styles()->registered[ $playlist_shortcode ];
		$this->assertContains( $playlist_shortcode, wp_styles()->queue );
		$this->assertEquals( array( 'wp-mediaelement' ), $style->deps );
		$this->assertEquals( $playlist_shortcode, $style->handle );
		$this->assertEquals( amp_get_asset_url( 'css/amp-playlist-shortcode.css' ), $style->src );
		$this->assertEquals( AMP__VERSION, $style->ver );
	}

	/**
	 * Test shortcode.
	 *
	 * @covers AMP_Playlist_Embed_Handler::shortcode()
	 * @covers AMP_Playlist_Embed_Handler::video_playlist()
	 */
	public function test_shortcode() {
		$attr     = $this->get_attributes( 'video' );
		$playlist = $this->instance->shortcode( $attr );
		$this->assertContains( '<amp-video', $playlist );
		$this->assertContains( '<amp-state', $playlist );
		$this->assertContains( $this->file_1, $playlist );
		$this->assertContains( $this->file_2, $playlist );
	}

	/**
	 * Test video_playlist.
	 *
	 * @covers AMP_Playlist_Embed_Handler::video_playlist()
	 */
	public function test_video_playlist() {
		$attr     = $this->get_attributes( 'video' );
		$data     = $this->instance->get_data( $attr );
		$playlist = $this->instance->video_playlist( $data );
		$this->assertContains( '<amp-video', $playlist );
		$this->assertContains( '<amp-state', $playlist );
		$this->assertContains( $this->file_1, $playlist );
		$this->assertContains( $this->file_2, $playlist );
		$this->assertContains( '[src]="wpPlaylist1[wpPlaylist1.selectedIndex].videoUrl"', $playlist );
		$this->assertContains( 'on="tap:AMP.setState({&quot;wpPlaylist1&quot;:{&quot;selectedIndex&quot;:0}})"', $playlist );
	}

	/**
	 * Test get_thumb_dimensions.
	 *
	 * @covers AMP_Playlist_Embed_Handler::get_thumb_dimensions()
	 */
	public function test_get_thumb_dimensions() {
		$dimensions = array(
			'height' => 60,
			'width'  => 60,
		);
		$track      = array(
			'thumb' => $dimensions,
		);
		$this->assertEquals( $dimensions, $this->instance->get_thumb_dimensions( $track ) );

		$dimensions = array(
			'height' => 68,
			'width'  => 59,
		);
		$track      = array(
			'thumb' => $dimensions,
		);
		$this->assertEquals( $dimensions, $this->instance->get_thumb_dimensions( $track ) );

		$dimensions          = array(
			'height' => 70,
			'width'  => 80.5,
		);
		$expected_dimensions = array(
			'height' => 52,
			'width'  => 60,
		);
		$track               = array(
			'thumb' => $dimensions,
		);
		$this->assertEquals( $expected_dimensions, $this->instance->get_thumb_dimensions( $track ) );

		$dimensions          = array(
			'width' => 80.5,
		);
		$track               = array(
			'thumb' => $dimensions,
		);
		$expected_dimensions = array(
			'height' => 48,
			'width'  => 60,
		);
		$this->assertEquals( $expected_dimensions, $this->instance->get_thumb_dimensions( $track ) );

		$track               = array(
			'thumb' => array(),
		);
		$expected_dimensions = array(
			'height' => AMP_Playlist_Embed_Handler::DEFAULT_THUMB_HEIGHT,
			'width'  => AMP_Playlist_Embed_Handler::DEFAULT_THUMB_WIDTH,
		);
		$this->assertEquals( $expected_dimensions, $this->instance->get_thumb_dimensions( $track ) );
	}

	/**
	 * Test audio_playlist.
	 *
	 * Logic for creating the videos copied from Tests_Media.
	 *
	 * @covers AMP_Playlist_Embed_Handler::audio_playlist()
	 */
	public function test_audio_playlist() {
		$attr     = $this->get_attributes( 'audio' );
		$playlist = $this->instance->audio_playlist( array() );
		$this->assertEquals( '', $playlist );

		$data     = $this->instance->get_data( $attr );
		$playlist = $this->instance->audio_playlist( $data );
		$this->assertContains( '<amp-carousel', $playlist );
		$this->assertContains( '<amp-audio', $playlist );
		$this->assertContains( $this->file_1, $playlist );
		$this->assertContains( $this->file_2, $playlist );
		$this->assertContains( 'tap:AMP.setState({&quot;wpPlaylist1&quot;:{&quot;selectedIndex&quot;:0}})"', $playlist );
	}

	/**
	 * Test tracks.
	 *
	 * @covers AMP_Playlist_Embed_Handler::print_tracks()
	 */
	public function test_tracks() {
		$type         = 'video';
		$attr         = $this->get_attributes( $type );
		$data         = $this->instance->get_data( $attr );
		$container_id = 'fooContainerId1';
		$state_id     = 'fooId1';
		$expected_on  = 'tap:AMP.setState({&quot;' . $state_id . '&quot;:{&quot;selectedIndex&quot;:0}})';

		ob_start();
		$this->instance->print_tracks( $state_id, $data['tracks'] );
		$tracks = ob_get_clean();
		$this->assertContains( '<div class="wp-playlist-tracks">', $tracks );
		$this->assertContains( $state_id, $tracks );
		$this->assertContains( $expected_on, $tracks );

		$attr        = $this->get_attributes( $type );
		$data        = $this->instance->get_data( $attr );
		$expected_on = 'tap:AMP.setState({&quot;' . $state_id . '&quot;:{&quot;selectedIndex&quot;:0}})';

		ob_start();
		$this->instance->print_tracks( $state_id, $data['tracks'] );
		$tracks = ob_get_clean();
		$this->assertContains( $expected_on, $tracks );
	}

	/**
	 * Test get_data.
	 *
	 * @covers AMP_Playlist_Embed_Handler::get_data()
	 */
	public function test_get_data() {
		$type = 'audio';
		$data = $this->instance->get_data( $this->get_attributes( $type ) );
		$this->assertEquals( $type, $data['type'] );
		$this->assertContains( $this->file_1, $data['tracks'][0]['src'] );
		$this->assertContains( $this->file_2, $data['tracks'][1]['src'] );
	}

	/**
	 * Test get_title.
	 *
	 * @covers AMP_Playlist_Embed_Handler::get_data()
	 */
	public function test_get_title() {
		$caption = 'Example caption';
		$title   = 'Media Title';
		$track   = array(
			'caption' => $caption,
		);

		$this->assertEquals( $caption, $this->instance->get_title( $track ) );

		$track = array(
			'title' => $title,
		);
		$this->assertEquals( $title, $this->instance->get_title( $track ) );

		$track = array(
			'caption' => $caption,
			'title'   => $title,
		);
		$this->assertEquals( $caption, $this->instance->get_title( $track ) );
		$this->assertEquals( null, $this->instance->get_title( array() ) );
	}

	/**
	 * Gets the shortcode attributes.
	 *
	 * @param string $type The type of shortcode attributes: 'audio' or 'video'.
	 * @return array $attrs The shortcode attributes.
	 */
	public function get_attributes( $type ) {
		if ( 'audio' === $type ) {
			$this->file_1 = 'example-audio-1.mp3';
			$this->file_2 = 'example-audio-2.mp3';
			$mime_type    = 'audio/mp3';
		} elseif ( 'video' === $type ) {
			$this->file_1 = 'example-video-1.mp4';
			$this->file_2 = 'example-video-2.mkv';
			$mime_type    = 'video/mp4';
		} else {
			return;
		}

		$files = array(
			$this->file_1,
			$this->file_2,
		);
		$ids   = $this->get_file_ids( $files, $mime_type );
		return array(
			'ids'  => implode( ',', $ids ),
			'type' => $type,
		);
	}

	/**
	 * Gets test file IDs.
	 *
	 * @param array  $files     The file names to create.
	 * @param string $mime_type The type of file.
	 * @return array $ids The IDs of the test files.
	 */
	public function get_file_ids( $files, $mime_type ) {
		$ids = array();
		foreach ( $files as $file ) {
			$ids[] = $this->factory()->attachment->create_object(
				$file,
				0,
				array(
					'post_mime_type' => $mime_type,
					'post_type'      => 'attachment',
				)
			);
		}
		return $ids;
	}

}
