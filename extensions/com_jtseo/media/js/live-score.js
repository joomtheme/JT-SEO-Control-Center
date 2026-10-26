(function () {
    'use strict';

    var config = (window.Joomla && Joomla.getOptions) ? Joomla.getOptions('plg_content_jtseo.liveScore', {}) : {};
    var labels = config.labels || {};
    var minWords = parseInt(config.minWords || 100, 10);

    function byId(id) {
        return document.getElementById(id);
    }

    function getValue(ids) {
        for (var i = 0; i < ids.length; i++) {
            var el = byId(ids[i]);
            if (el && typeof el.value === 'string' && el.value.trim() !== '') {
                return el.value.trim();
            }
        }
        return '';
    }

    function getTextareaValue(ids) {
        for (var i = 0; i < ids.length; i++) {
            var el = byId(ids[i]);
            if (el && typeof el.value === 'string') {
                return el.value;
            }
        }
        return '';
    }

    function plainText(html) {
        var div = document.createElement('div');
        div.innerHTML = html || '';
        return (div.textContent || div.innerText || '').replace(/\s+/g, ' ').trim();
    }

    function excerpt(text, max) {
        text = text || '';
        return text.length > max ? text.substring(0, max) : text;
    }

    function wordCount(text) {
        text = (text || '').trim();
        if (!text) {
            return 0;
        }
        var matches = text.match(/[\p{L}\p{N}]+/gu);
        return matches ? matches.length : text.split(/\s+/).filter(Boolean).length;
    }

    function bucket(score) {
        if (score >= 90) return 'excellent';
        if (score >= 75) return 'good';
        if (score >= 55) return 'fair';
        return 'weak';
    }

    function labelForBucket(name) {
        return labels[name] || name;
    }

    function calculate() {
        var title = getValue(['jform_attribs_jtseo_title', 'jform_title']);
        var titleLength = title.length;
        var jtDescription = getValue(['jform_attribs_jtseo_description']);
        var metaDescription = getValue(['jform_metadesc']);
        var articleText = plainText(getTextareaValue(['jform_articletext', 'jform_introtext']));
        var description = jtDescription || metaDescription || excerpt(articleText, 180);
        var explicitDescription = !!(jtDescription || metaDescription);
        var descriptionLength = description.length;
        var ogImage = getValue(['jform_attribs_jtseo_og_image', 'jform_images_image_intro', 'jform_images_image_fulltext']);
        var robotsIndexEl = byId('jform_attribs_jtseo_robots_index');
        var robotsIndex = robotsIndexEl ? robotsIndexEl.value : '';
        var canonical = getValue(['jform_attribs_jtseo_canonical']);
        var words = wordCount(articleText);
        var score = 100;
        var notes = [];

        if (titleLength < 30 || titleLength > 65) {
            score -= 8;
            notes.push(labels.titleLength);
        }

        if (!explicitDescription) {
            score -= 25;
            notes.push(labels.missingDescription);
        } else if (descriptionLength < 120) {
            score -= 10;
            notes.push(labels.shortDescription);
        } else if (descriptionLength > 160) {
            score -= 5;
            notes.push(labels.longDescription);
        }

        if (!description.trim()) {
            score -= 10;
        }

        if (!ogImage) {
            score -= 15;
            notes.push(labels.missingImage);
        }

        if (robotsIndex === 'noindex') {
            score -= 30;
            notes.push(labels.noindex);
        }

        if (minWords > 0 && words > 0 && words < minWords) {
            score -= 10;
            notes.push(labels.lowWordCount);
        }

        if (canonical) {
            notes.push(labels.canonicalOverride);
        }

        score = Math.max(0, Math.min(100, score));
        notes = notes.filter(Boolean).filter(function (value, index, array) {
            return array.indexOf(value) === index;
        });

        if (!notes.length) {
            notes.push(labels.goodNote);
        }

        return {
            score: score,
            bucket: bucket(score),
            notes: notes,
            descriptionLength: descriptionLength,
            wordCount: words,
            minWords: minWords
        };
    }

    function render(card) {
        var result = calculate();
        var badge = card.querySelector('[data-jtseo-live-badge]');
        var bar = card.querySelector('[data-jtseo-live-bar]');
        var notes = card.querySelector('[data-jtseo-live-notes]');
        var meta = card.querySelector('[data-jtseo-live-meta]');
        var classes = ['is-excellent', 'is-good', 'is-fair', 'is-weak'];

        if (!badge || !bar || !notes || !meta) {
            return;
        }

        badge.textContent = result.score + '/100 · ' + labelForBucket(result.bucket);
        bar.style.width = result.score + '%';
        classes.forEach(function (cls) {
            badge.classList.remove(cls);
            bar.classList.remove(cls);
        });
        badge.classList.add('is-' + result.bucket);
        bar.classList.add('is-' + result.bucket);

        notes.textContent = '';
        result.notes.forEach(function (note) {
            var span = document.createElement('span');
            span.className = 'jtseo-live-score-note';
            span.textContent = note;
            notes.appendChild(span);
        });

        meta.textContent = (labels.meta || 'Description: %s chars · Content: %s words · Minimum: %s words')
            .replace('%s', result.descriptionLength)
            .replace('%s', result.wordCount)
            .replace('%s', result.minWords);
    }

    function init() {
        var titleField = byId('jform_attribs_jtseo_title');
        if (!titleField || byId('jtseo-live-score-card')) {
            return;
        }

        var group = titleField.closest('.control-group, .form-group, .mb-3') || titleField.parentNode;
        var card = document.createElement('div');
        card.id = 'jtseo-live-score-card';
        card.className = 'jtseo-live-score-card';
        card.innerHTML = '<div class="jtseo-live-score-head"><div><p class="jtseo-live-score-title"></p><p class="jtseo-live-score-subtitle"></p></div><span class="jtseo-live-score-badge is-weak" data-jtseo-live-badge>0/100</span></div><div class="jtseo-live-score-track"><div class="jtseo-live-score-bar is-weak" data-jtseo-live-bar></div></div><div class="jtseo-live-score-notes" data-jtseo-live-notes></div><div class="jtseo-live-score-meta" data-jtseo-live-meta></div>';
        card.querySelector('.jtseo-live-score-title').textContent = labels.title || 'Live SEO score';
        card.querySelector('.jtseo-live-score-subtitle').textContent = labels.subtitle || 'Updates while you edit this article.';

        if (group && group.parentNode) {
            group.parentNode.insertBefore(card, group);
        }

        var ids = [
            'jform_title',
            'jform_metadesc',
            'jform_articletext',
            'jform_introtext',
            'jform_attribs_jtseo_title',
            'jform_attribs_jtseo_description',
            'jform_attribs_jtseo_canonical',
            'jform_attribs_jtseo_og_image',
            'jform_attribs_jtseo_robots_index',
            'jform_images_image_intro',
            'jform_images_image_fulltext'
        ];

        ids.forEach(function (id) {
            var el = byId(id);
            if (el) {
                el.addEventListener('input', function () { render(card); });
                el.addEventListener('change', function () { render(card); });
            }
        });

        render(card);
        window.setTimeout(function () { render(card); }, 500);
        window.setTimeout(function () { render(card); }, 1500);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
}());
