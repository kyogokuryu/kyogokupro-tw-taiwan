/* GET_SAME_CATEGORY_PRODUCTS */
SELECT
	c4.*
FROM
	dtb_product c4
INNER JOIN (
	SELECT
		c3.product_id
	FROM
		dtb_product_category c3
	INNER JOIN (
		SELECT
			c2.id
		FROM
			dtb_product_category c1
		INNER JOIN dtb_category c2 ON
			c1.category_id = c2.id
		WHERE
			c1.product_id = :product_id
		GROUP BY
			c2.id
		ORDER BY
			c2.hierarchy DESC, c2.sort_no ASC
		LIMIT 1 ) c4 ON
		c3.category_id = c4.id ) c5 ON
	c4.id = c5.product_id
WHERE
	c4.product_status_id = 1
LIMIT 4;