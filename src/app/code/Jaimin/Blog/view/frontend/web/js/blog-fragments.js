/**
 * Jaimin_Blog — GraphQL fragment definitions.
 *
 * These are plain strings prepended to GraphQL queries.
 * Define fields once here, spread them in any query.
 *
 * Usage in Alpine.js component:
 *   const query = `
 *       ${window.BlogFragments.POST_CARD}
 *       ${window.BlogFragments.PAGE_INFO}
 *       query GetBlogPosts(...) {
 *           blogPosts(...) {
 *               total_count
 *               page_info { ...PageInfoFields }
 *               items { ...BlogPostCard }
 *           }
 *       }
 *   `;
 */
window.BlogFragments = {

    // Card fields — used on list page, search results, widgets
    // Does NOT include content (can be 64KB per post)
    POST_CARD: `
        fragment BlogPostCard on BlogPost {
            post_id
            title
            author
            url_key
            status
            created_at
            meta_description
        }
    `,

    // Full fields — used on single post detail page only
    POST_DETAIL: `
        fragment BlogPostDetail on BlogPost {
            post_id
            title
            content
            author
            url_key
            status
            meta_title
            meta_description
            created_at
            updated_at
        }
    `,

    // Pagination metadata — reused in every paginated query
    PAGE_INFO: `
        fragment PageInfoFields on BlogPostPageInfo {
            page_size
            current_page
            total_pages
        }
    `,
};