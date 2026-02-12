<?php
get_header();
?>

<section class="records-archive">
    <div class="records-archive__inner">

        <header class="records-archive__header">
            <h2 class="records-archive__title">Records</h2>
        </header>

        <?php if (have_posts()) : ?>
            <div class="records-grid">

                <?php while (have_posts()) : the_post();

                    $artist  = trim((string) get_post_meta(get_the_ID(), '_nyiti_artist', true));
                    $album   = trim((string) get_post_meta(get_the_ID(), '_nyiti_album', true));
                    $variant = trim((string) get_post_meta(get_the_ID(), '_nyiti_variant', true));

                    // What you want to show on the archive card:
                    // Line 1: album (title)
                    // Line 2: artist
                    // Line 3: variant
                    $display_title = ($album !== '') ? $album : get_the_title();
                ?>

                    <article class="record-card">
                        <a class="record-card__link" href="<?php the_permalink(); ?>">

                            <div class="record-card__media">
                                <?php if (has_post_thumbnail()) : ?>
                                    <?php the_post_thumbnail('medium', ['class' => 'record-card__image']); ?>
                                <?php else : ?>
                                    <div class="record-card__placeholder"></div>
                                <?php endif; ?>
                            </div>

                            <div class="record-card__body">
                                <h3 class="record-card__title"><?php echo esc_html($display_title); ?></h3>

                                <?php if ($artist !== '') : ?>
                                    <p class="record-card__artist"><?php echo esc_html($artist); ?></p>
                                <?php endif; ?>

                                <?php if ($variant !== '') : ?>
                                    <p class="record-card__variant"><?php echo esc_html($variant); ?></p>
                                <?php endif; ?>
                            </div>

                        </a>
                    </article>

                <?php endwhile; ?>

            </div>
        <?php else : ?>
            <p>No records found.</p>
        <?php endif; ?>

    </div>
</section>

<?php
get_footer();
