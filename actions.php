<?php
/**
 * Created by PhpStorm.
 * User: Johan
 * Date: 2015-06-21
 * Time: 15:39
 */

use Aws\S3\S3Client;

return [

    'createMultipartUpload' => function(S3Client $client, array $config) {
        $model = $client->createMultipartUpload([
            'Bucket' => $config['bucket'],
            'Key' => $_POST['Key'],
            'ContentType' => $_POST['ContentType']
        ]);

        return $model->toArray();
    },

    'abortMultipartUpload' => function(S3Client $client, array $config) {
        $model = $client->abortMultipartUpload([
            'Bucket' => $config['bucket'],
            'Key' => $_POST['Key'],
            'UploadId' => $_POST['UploadId']
        ]);

        return $model->toArray();
    },

    'completeMultipartUpload' => function(S3Client $client, array $config) {
        $model = $client->completeMultipartUpload([
            'Bucket' => $config['bucket'],
            'Key' => $_POST['Key'],
            'Parts' => $_POST['Parts'],
            'UploadId' => $_POST['UploadId']
        ]);

        return $model->toArray();
    },

    'listMultipartUploads' => function(S3Client $client, array $config) {
        $model = $client->listMultipartUploads([
            'Bucket' => $config['bucket']
        ]);

        return $model->toArray();
    },

    'signUploadPart' => function(S3Client $client, array $config) {
        $command = $client->getCommand('uploadPart', [
            'Bucket' => $config['bucket'],
            'Body' => '',
            'Key' => $_POST['Key'],
            'PartNumber' => $_POST['PartNumber'],
            'UploadId' => $_POST['UploadId'],
        ]);

        return [
            'Url' => $command->createPresignedUrl('+10 minutes')
        ];
    },

    'deleteObject' => function(S3Client $client, array $config) {
        $model = $client->deleteObject([
            'Bucket' => $config['bucket'],
            'Key' => $_POST['Key']
        ]);

        return $model->toArray();
    }

];
