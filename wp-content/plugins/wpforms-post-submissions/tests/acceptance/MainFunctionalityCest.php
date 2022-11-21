<?php

namespace WPFormsPostSubmissionsTests;

use Exception;
use WPForms\Cest;
use AcceptanceTester;
use WPForms\PageObjects\Builder;
use WPForms\PageObjects\FormsOverview;
use WPForms\PageObjects\FrontEndForm;
use WPForms\PageObjects\WordPress\PostEditPage;
use WPForms\PageObjects\WordPress\PostsListPage;

/**
 * Tests main functionality for the Posts Submission addon using the Rich Text field.
 *
 * Class MainFunctionalityCest.
 */
class MainFunctionalityCest extends Cest {

	/**
	 * Array with the IDs of the fields.
	 *
	 * @var array $fieldIds
	 */
	private $fieldIds = [
		'title'         => null,
		'content'       => null,
		'excerpt'       => null,
		'featuredImage' => null,
		'customMeta'    => null,
	];

	/**
	 * Array with test content.
	 *
	 * @var string[]
	 */
	private $testContent = [
		'title'         => 'CC Post',
		'content'       => '<h1>WPForms rocks!</h1><em><strong>CC Content</strong></em>',
		'excerpt'       => 'CC Excerpt',
		'featuredImage' => null,
		'customMeta'    => 'CC Custom Meta',
	];

	const CUSTOM_META_LABEL = 'CCMetaLabel';

	/**
	 * URL of the page containing the test form.
	 *
	 * @var string $pageWithFormUrl
	 */
	private $pageWithFormUrl;

	/**
	 * Trigger all validation errors for the address field with the US schema.
	 *
	 * @param AcceptanceTester $i             Actor.
	 * @param FormsOverview    $formsOverview The Forms Overview Page.
	 * @param Builder          $builder       The Builder Page.
	 * @param PostEditPage     $postEdit      The Post Edit Page.
	 * @param FrontEndForm     $frontEndForm  The Front End Entry creation page.
	 * @param PostsListPage    $postLists     The Posts Listing page.
	 *
	 * @throws Exception
	 *
	 * @group pro
	 * @group latestwp
	 */
	public function postSubmissions( AcceptanceTester $i, FormsOverview $formsOverview, Builder $builder, PostEditPage $postEdit, FrontEndForm $frontEndForm, PostsListPage $postLists ) {

		$i->loginAsAdmin();
		$this->createForm( $i, $formsOverview, $builder );
		$this->embedForm( $builder, $postEdit );
		$this->createEntry( $i, $frontEndForm );
		$this->assertPostCreated( $i, $postLists, $postEdit );
	}

	/**
	 * Create a form with the testing fields, enables the form abandonment and adds a related notification.
	 *
	 * @param AcceptanceTester $i             The Actor.
	 * @param FormsOverview    $formsOverview The Forms Overview Page.
	 * @param Builder          $builder       The Builder Page.
	 *
	 * @throws Exception
	 */
	private function createForm( AcceptanceTester $i, FormsOverview $formsOverview, Builder $builder ) {

		$formsOverview->visitFormsOverviewPage();
		$formsOverview->addNewForm();
		$builder->chooseTemplate();
		$this->fieldIds['title']         = $builder->addField( 'text' );
		$this->fieldIds['content']       = $builder->addField( 'richtext' );
		$this->fieldIds['excerpt']       = $builder->addField( 'textarea' );
		$this->fieldIds['featuredImage'] = $builder->addField(
            'file-upload',
			[
				'style' => 'classic',
            ]
        );
		$this->fieldIds['customMeta']    = $builder->addField( 'text' );

		$builder->setSettings(
			[
				'post_submissions' => [
					'[post_submissions]'          => true,
					'[post_submissions_title]'    => $this->fieldIds['title'],
					'[post_submissions_content]'  => $this->fieldIds['content'],
					'[post_submissions_excerpt]'  => $this->fieldIds['excerpt'],
					'[post_submissions_featured]' => $this->fieldIds['featuredImage'],
					'[post_submissions_status]'   => 'publish',
				],
			]
		);
		$builder->setCustomPostMetaInAddon( 'post_submissions', [ self::CUSTOM_META_LABEL => $this->fieldIds['customMeta'] ] );
		$builder->save();
	}

	/**
	 * Embeds the form in a new page.
	 *
	 * @param Builder      $builder  The Builder Page.
	 * @param PostEditPage $postEdit The Post Edit Page.
	 *
	 * @throws Exception
	 */
	private function embedForm( Builder $builder, PostEditPage $postEdit ) {

		$builder->embedForm( 'new', 'CCForm Page' );
		$postEdit->publish();
		$this->pageWithFormUrl = $postEdit->getPostPermalink();
	}

	/**
	 * Creates an entry in the front-end.
	 *
	 * @param AcceptanceTester $i            Actor.
	 * @param FrontEndForm     $frontEndForm The Front End Entry creation page.
	 *
	 * @throws Exception
	 */
	private function createEntry( AcceptanceTester $i, FrontEndForm $frontEndForm ) {

		$i->amOnUrl( $this->pageWithFormUrl );

		$frontEndForm->fillSingleTextField( $this->fieldIds['title'], $this->testContent['title'] );
		$frontEndForm->fillRichTextFieldInTextMode( $this->fieldIds['content'], $this->testContent['content'] );
		$frontEndForm->fillTextAreaField( $this->fieldIds['excerpt'], $this->testContent['excerpt'] );
		$frontEndForm->attachInClassicFileUploadField( $this->fieldIds['featuredImage'], 'testImages/tulips.jpg' );
		$frontEndForm->fillSingleTextField( $this->fieldIds['customMeta'], $this->testContent['customMeta'] );
		$frontEndForm->clickSubmit();
	}

	/**
	 * Asserts abandoned entry data in back-end.
     *
	 * @param AcceptanceTester $i         Actor.
	 * @param PostsListPage    $postLists The Posts Listing page.
	 * @param PostEditPage     $postEdit  The Post Edit Page.
	 *
	 * @throws Exception
	 */
	private function assertPostCreated( AcceptanceTester $i, $postLists, $postEdit ) {

		$postLists->show();
		$postLists->openPostForEditing( $this->testContent['title'] );

		$postEdit->seeTitle( $this->testContent['title'] );
		$postEdit->seePostStatus( 'publish' );
		$postEdit->seeContent( $this->testContent['content'] );

		$postId = $postEdit->getPostId();

		$i->runShellCommand( 'wp post list --post_type=attachment --field=ID' );
		$attachmentId = $i->grabShellOutput();

		$this->seePostMeta( $i, $postId, '_thumbnail_id', $attachmentId );
		$this->seePostMeta( $i, $postId, self::CUSTOM_META_LABEL, $this->testContent['customMeta'] );
	}

	/**
	 * Asserts given post meta is found in post.
	 *
	 * @param AcceptanceTester $i         The Actor.
	 * @param int              $postId    ID of the post.
	 * @param string           $metaKey   Meta key.
	 * @param string           $metaValue Meta value.
	 */
	private function seePostMeta( AcceptanceTester $i, $postId, $metaKey, $metaValue ) {

		$i->runShellCommand( sprintf( 'wp post meta get %d %s', $postId, $metaKey ) );
		$i->seeInShellOutput( $metaValue );
	}
}
