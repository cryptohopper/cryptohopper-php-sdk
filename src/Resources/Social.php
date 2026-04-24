<?php

declare(strict_types=1);

namespace Cryptohopper\Sdk\Resources;

use Cryptohopper\Sdk\Transport;

/**
 * `$client->social` — profiles, feed, posts, conversations, social graph.
 *
 * Largest resource in the SDK (27 methods).
 */
final class Social
{
    public function __construct(private readonly Transport $transport)
    {
    }

    // ─── Profiles ─────────────────────────────────────────────────────

    public function getProfile(int|string $aliasOrId): mixed
    {
        return $this->transport->request('GET', '/social/getprofile', query: ['alias' => $aliasOrId]);
    }

    /** @param array<string, mixed> $data */
    public function editProfile(array $data): mixed
    {
        return $this->transport->request('POST', '/social/editprofile', body: $data);
    }

    public function checkAlias(string $alias): mixed
    {
        return $this->transport->request('GET', '/social/checkalias', query: ['alias' => $alias]);
    }

    // ─── Feed / discovery ─────────────────────────────────────────────

    /** @param array<string, mixed> $params */
    public function getFeed(array $params = []): mixed
    {
        return $this->transport->request('GET', '/social/getfeed', query: $params !== [] ? $params : null);
    }

    public function getTrends(): mixed
    {
        return $this->transport->request('GET', '/social/gettrends');
    }

    public function whoToFollow(): mixed
    {
        return $this->transport->request('GET', '/social/whotofollow');
    }

    public function search(string $query): mixed
    {
        return $this->transport->request('GET', '/social/search', query: ['q' => $query]);
    }

    // ─── Notifications ────────────────────────────────────────────────

    /** @param array<string, mixed> $params */
    public function getNotifications(array $params = []): mixed
    {
        return $this->transport->request('GET', '/social/getnotifications', query: $params !== [] ? $params : null);
    }

    // ─── Conversations / messages ─────────────────────────────────────

    public function getConversationList(): mixed
    {
        return $this->transport->request('GET', '/social/getconversationlist');
    }

    public function getConversation(int|string $conversationId): mixed
    {
        return $this->transport->request('GET', '/social/loadconversation', query: ['conversation_id' => $conversationId]);
    }

    /** @param array<string, mixed> $data */
    public function sendMessage(array $data): mixed
    {
        return $this->transport->request('POST', '/social/sendmessage', body: $data);
    }

    public function deleteMessage(int|string $messageId): mixed
    {
        return $this->transport->request('POST', '/social/deletemessage', body: ['message_id' => $messageId]);
    }

    // ─── Posts ────────────────────────────────────────────────────────

    /** @param array<string, mixed> $data */
    public function createPost(array $data): mixed
    {
        return $this->transport->request('POST', '/social/post', body: $data);
    }

    public function getPost(int|string $postId): mixed
    {
        return $this->transport->request('GET', '/social/getpost', query: ['post_id' => $postId]);
    }

    public function deletePost(int|string $postId): mixed
    {
        return $this->transport->request('POST', '/social/deletepost', body: ['post_id' => $postId]);
    }

    public function pinPost(int|string $postId): mixed
    {
        return $this->transport->request('POST', '/social/pinpost', body: ['post_id' => $postId]);
    }

    // ─── Comments ─────────────────────────────────────────────────────

    public function getComment(int|string $commentId): mixed
    {
        return $this->transport->request('GET', '/social/getcomment', query: ['comment_id' => $commentId]);
    }

    public function getComments(int|string $postId): mixed
    {
        return $this->transport->request('GET', '/social/getcomments', query: ['post_id' => $postId]);
    }

    public function deleteComment(int|string $commentId): mixed
    {
        return $this->transport->request('POST', '/social/deletecomment', body: ['comment_id' => $commentId]);
    }

    // ─── Media ────────────────────────────────────────────────────────

    public function getMedia(int|string $mediaId): mixed
    {
        return $this->transport->request('GET', '/social/getmedia', query: ['media_id' => $mediaId]);
    }

    // ─── Social graph ─────────────────────────────────────────────────

    public function follow(int|string $aliasOrId): mixed
    {
        return $this->transport->request('POST', '/social/follow', body: ['alias' => $aliasOrId]);
    }

    public function getFollowers(int|string $aliasOrId): mixed
    {
        return $this->transport->request('GET', '/social/followers', query: ['alias' => $aliasOrId]);
    }

    public function getFollowing(int|string $aliasOrId): mixed
    {
        return $this->transport->request('GET', '/social/following', query: ['alias' => $aliasOrId]);
    }

    public function getFollowingProfiles(int|string $aliasOrId): mixed
    {
        return $this->transport->request('GET', '/social/followingprofiles', query: ['alias' => $aliasOrId]);
    }

    // ─── Engagement ───────────────────────────────────────────────────

    public function like(int|string $postId): mixed
    {
        return $this->transport->request('POST', '/social/like', body: ['post_id' => $postId]);
    }

    public function repost(int|string $postId): mixed
    {
        return $this->transport->request('POST', '/social/repost', body: ['post_id' => $postId]);
    }

    // ─── Moderation ───────────────────────────────────────────────────

    public function blockUser(int|string $aliasOrId): mixed
    {
        return $this->transport->request('POST', '/social/blockuser', body: ['alias' => $aliasOrId]);
    }
}
