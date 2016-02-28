/**
 * Created by Johan on 2015-06-19.
 */

/**
 * Super simple queue that executes deferred-returning functions in order
 * When the queue is empty, queue.deferred is resolved
 * @constructor
 * @param {(Function -> jQuery.Deferred)[]} Array of functions returning a jQuery.Deferred
 */
function PromiseQueue(funcs) {
    this.funcs = funcs;
    this.afterEach = jQuery.noop();
}

/**
 * Run the queue from a specific index and continue recursively
 * @param {int} idx Queue index
 * @param {jQuery.Deferred} deferred to resolve when the queue is complete
 * @returns {boolean}
 */
PromiseQueue.prototype.runFrom = function(idx, deferred) {
    if ( idx >= this.funcs.length ) {
        deferred.resolve();
        return true;
    }
    var self = this;
    var item = this.funcs[idx];
    item().then(function(result) {
        self.afterEach(result);
        self.runFrom(idx + 1, deferred);
    }, null, function(progress) {
        deferred.notify(progress);
    });
    return false;
};

/**
 * Run the queue
 */
PromiseQueue.prototype.run = function() {
    var deferred = jQuery.Deferred();
    this.runFrom(0, deferred);
    return deferred.promise();
};
