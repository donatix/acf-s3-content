/**
 * Created by Johan on 2015-06-19.
 */

/**
 * Client side wrapper for app.php
 * @constructor
 */
function S3Proxy(proxyUrl) {
    this.proxyUrl = proxyUrl;
}

/**
 * @returns {jQuery.Deferred}
 */
S3Proxy.prototype.createMultipartUpload = function(key, contentType) {
    return jQuery.ajax({
        url: this.proxyUrl + '&command=createMultipartUpload',
        method: 'post',
        dataType: 'json',
        data: {
            Key: key,
            ContentType: contentType
        }
    });
};

/**
 * @returns {jQuery.Deferred}
 */
S3Proxy.prototype.listMultipartUploads = function() {
    return jQuery.ajax({
        url: this.proxyUrl + '&command=listMultipartUploads',
        method: 'get',
        dataType: 'json'
    });
};

/**
 * @returns {jQuery.Deferred}
 */
S3Proxy.prototype.abortMultipartUpload = function(key, uploadId) {
    return jQuery.ajax({
        url: this.proxyUrl + '&command=abortMultipartUpload',
        method: 'post',
        dataType: 'json',
        data: {
            Key: key,
            UploadId: uploadId
        }
    });
};

/**
 * @returns {jQuery.Deferred}
 */
S3Proxy.prototype.completeMultipartUpload = function(key, uploadId, parts) {
    return jQuery.ajax({
        url: this.proxyUrl + '&command=completeMultipartUpload',
        method: 'post',
        dataType: 'json',
        data: {
            Key: key,
            UploadId: uploadId,
            Parts: parts
        }
    });
};

/**
 * @returns {jQuery.Deferred}
 */
S3Proxy.prototype.signUploadPart = function(key, uploadId, partNumber) {
    return jQuery.ajax({
        url: this.proxyUrl + '&command=signUploadPart',
        method: 'post',
        dataType: 'json',
        data: {
            Key: key,
            UploadId: uploadId,
            PartNumber: partNumber
        }
    });
};
