define('wikia.nodeFinder', function () {
	'use strict';

	/**
	 * Find first element described by selector after set offset top value.
	 *
	 * @param {Node} container parent container
	 * @param {String} selector selector to search
	 * @param {Integer} boundaryOffsetTop boundary offset top value
	 * @return {Node} first element after set offset top value
	 */
	function getChildByOffsetTop(container, selector, boundaryOffsetTop) {
		var elements = container.querySelectorAll(selector),
			length = elements.length,
			i;

		for (i = 0; i < length; i++) {
			if (elements[i].offsetTop > boundaryOffsetTop) {
				return elements[i];
			}
		}

		return null;
	}

	/**
	 * Find the closest previous visible element.
	 *
	 * @param {Node} referenceNode node to start searching from (exclusively)
	 * @return {Node} or null if not found
	 */
	function getPreviousVisibleSibling(referenceNode) {
		var node = referenceNode.previousSibling;

		while (node && !isVisibleForSloth(node)) {
			node = node.previousSibling;
		}

		return node;
	}

	/**
	 * Find the last visible element.
	 *
	 * @param {Node} container parent container
	 * @return {Node} or null if not found
	 */
	function getLastVisibleChild(container) {
		var child = container.lastChild;

		if (isVisibleForSloth(child)) {
			return child;
		}

		return getPreviousVisibleSibling(child);
	}

	/**
	 * Simple check if an element is visible for Sloth module to be able to bind to it.
	 *
	 * @param {Node} element node to be checked
	 * @return {Bool}
	 */
	function isVisibleForSloth(element) {
		return element.scrollWidth || element.scrollHeight;
	}

	return {
		getChildByOffsetTop: getChildByOffsetTop,
		getPreviousVisibleSibling: getPreviousVisibleSibling,
		getLastVisibleChild: getLastVisibleChild
	};
});
