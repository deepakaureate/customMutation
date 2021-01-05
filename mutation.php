<?php
/*
Plugin Name: Add Mutation for custom post type
*/
 
add_action('graphql_register_types', function () {

	register_graphql_mutation('createTestimonial', [
		'inputFields' => [
			'firstName' => [
				'type' => 'String',
				'description' => 'User First Name',
			],
			'lastName' => [
				'type' => 'String',
				'description' => 'User Last Name',
			],
			'favoriteFood' => [
				'type' => 'String',
				'description' => 'User Favorite Food',
			],
			'message' => [
				'type' => 'String',
				'description' => 'User Message',
			],
		],
		'outputFields' => [
			'success' => [
				'type' => 'Boolean',
				'description' => 'Whether or not data was stored successfully',
				'resolve' => function ($payload, $args, $context, $info) {
					return isset($payload['success']) ? $payload['success'] : null;
				}
			],
			'data' => [
				'type' => 'String',
				'description' => 'Payload of submitted fields',
				'resolve' => function ($payload, $args, $context, $info) {
					return isset($payload['data']) ? $payload['data'] : null;
				}
			]
		],
		'mutateAndGetPayload' => function ($input, $context, $info) {

			if (!class_exists('ACF')) return [
				'success' => false,
				'data' => 'ACF is not installed'
			];

			$sanitized_data = [];
			$errors = [];
			$acceptable_fields = [
				'firstName' => 'field_5ff427b756420',
				'lastName' => 'field_5ff427d156421',
				'favoriteFood' => 'field_5ff427dc56422',
				'message' => 'field_5ff427e756423',
			];

			foreach ($acceptable_fields as $field_key => $acf_key) {
				if (!empty($input[$field_key])) {
					$sanitized_data[$field_key] = sanitize_text_field($input[$field_key]);
				} else {
					$errors[] = $field_key . ' was not filled out.';
				}
			}

			if (!empty($errors)) return [
				'success' => false,
				'data' => $errors
			];

			$form_submission = wp_insert_post([
				'post_type' => 'testimonial',
                'post_title' => $sanitized_data['firstName'] . ' ' . $sanitized_data['lastName'],
                'post_status' => "publish"
			], true);

			if (is_wp_error($form_submission)) return [
				'success' => false,
				'data' => $form_submission->get_error_message()
			];

			foreach ($acceptable_fields as $field_key => $acf_key) {
				update_field($acf_key, $sanitized_data[$field_key], $form_submission);
			}

			return [
				'success' => true,
				'data' => json_encode($sanitized_data)
			];

		}
    ]);
    register_graphql_mutation('deleteTestimonial', [
		'inputFields' => [
			'id'          => [
				'type'        => [
					'non_null' => 'ID',
				],
				// translators: The placeholder is the name of the post's post_type being deleted
				'description' => sprintf( __( 'The ID of the %1$s to delete', 'wp-graphql' ), $post_type_object->graphql_single_name ),
			],
		],
		'outputFields' => [
			'success' => [
				'type' => 'Boolean',
				'description' => 'Whether or not data was stored successfully',
				'resolve' => function ($payload, $args, $context, $info) {
					return isset($payload['success']) ? $payload['success'] : null;
				}
			],
			'data' => [
				'type' => 'String',
				'description' => 'Payload of submitted fields',
				'resolve' => function ($payload, $args, $context, $info) {
					return isset($payload['data']) ? $payload['data'] : null;
				}
			]
		],
		'mutateAndGetPayload' => function ($input, $context, $info) {
			$form_submission = wp_delete_post(sanitize_text_field($input['id']));
			if (is_wp_error($form_submission)) return [
				'success' => false,
				'data' => $form_submission->get_error_message()
			];
			return [
				'success' => true,
				'data' => json_encode(sanitize_text_field($input['id']))
			];

		}
	]);

});

