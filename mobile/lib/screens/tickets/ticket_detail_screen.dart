import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';
import '../../core/theme/app_theme.dart';
import '../../core/widgets/app_button.dart';
import '../../core/widgets/app_card.dart';
import '../../core/widgets/app_input.dart';
import '../../core/widgets/status_badge.dart';
import '../../core/widgets/skeleton_loader.dart';
import '../../core/widgets/empty_state.dart';
import '../../core/widgets/error_state.dart';
import '../../core/widgets/app_toast.dart';
import '../../core/widgets/lightbox.dart';
import '../../core/widgets/csat_dialog.dart';
import '../../core/widgets/sla_card.dart';
import '../../models/ticket_sla.dart';
import '../../core/config/app_config.dart';
import '../../models/ticket.dart';
import '../auth/login_screen.dart';
import 'ticket_list_screen.dart';
import 'widgets/ai_assistant_card.dart';

final cannedResponsesProvider = FutureProvider.autoDispose<List<Map<String, dynamic>>>((ref) async {
  final repo = ref.read(ticketRepositoryProvider);
  return repo.getCannedResponses();
});

final ticketDetailsProvider = FutureProvider.family
    .autoDispose<Map<String, dynamic>, int>((ref, ticketId) async {
  final repo = ref.read(ticketRepositoryProvider);
  return repo.getTicketDetails(ticketId);
});

final ticketMessagesProvider = FutureProvider.family
    .autoDispose<List<Map<String, dynamic>>, int>((ref, ticketId) async {
  final repo = ref.read(ticketRepositoryProvider);
  return repo.getTicketMessages(ticketId);
});

final ticketTechniciansProvider = FutureProvider.family
    .autoDispose<List<Map<String, dynamic>>, int>((ref, ticketId) async {
  final repo = ref.read(ticketRepositoryProvider);
  return repo.getTechnicians(ticketId);
});

final ticketActivitiesProvider = FutureProvider.family
    .autoDispose<Map<String, dynamic>, int>((ref, ticketId) async {
  final repo = ref.read(ticketRepositoryProvider);
  return repo.getTicketActivities(ticketId);
});

class TicketDetailScreen extends ConsumerStatefulWidget {
  final Ticket ticket;

  const TicketDetailScreen({super.key, required this.ticket});

  @override
  ConsumerState<TicketDetailScreen> createState() => _TicketDetailScreenState();
}

class _TicketDetailScreenState extends ConsumerState<TicketDetailScreen> {
  final TextEditingController _replyController = TextEditingController();
  bool _isSubmitting = false;

  String? _selectedStatus;
  int? _selectedTechnicianId;

  final List<Map<String, String>> _pendingAttachments = [];

  final List<String> _statuses = [
    'Open',
    'Assigned',
    'In Progress',
    'Waiting for Employee',
    'Waiting for Technician',
    'Approved',
    'Rejected',
    'Resolved',
    'Closed',
    'Cancelled'
  ];

  @override
  void initState() {
    super.initState();
    _selectedStatus = widget.ticket.status;
  }

  @override
  void dispose() {
    _replyController.dispose();
    super.dispose();
  }

  void _addSampleAttachment(String fileName, String fileType) {
    const sampleBase64 =
        'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';
    setState(() {
      _pendingAttachments.add({
        'fileName': fileName,
        'fileType': fileType,
        'base64': sampleBase64,
      });
    });
  }

  void _showAddAttachmentModal() {
    showModalBottomSheet(
      context: context,
      shape: const RoundedRectangleBorder(
        borderRadius:
            BorderRadius.vertical(top: Radius.circular(AppRadius.modal)),
      ),
      builder: (context) => Container(
        padding: const EdgeInsets.all(AppSpacing.lg),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text('Add Attachment',
                style: TextStyle(
                    fontSize: 18,
                    fontWeight: FontWeight.bold,
                    color: AppColors.textPrimary)),
            const SizedBox(height: AppSpacing.xs),
            const Text('Choose a file to attach to your reply:',
                style: TextStyle(color: AppColors.textSecondary, fontSize: 13)),
            const SizedBox(height: AppSpacing.md),
            ListTile(
              leading: const CircleAvatar(
                  backgroundColor: AppColors.primaryLight,
                  child: Icon(Icons.image, color: AppColors.primary)),
              title: const Text('Screenshot_Log.png',
                  style: TextStyle(fontWeight: FontWeight.w600, fontSize: 14)),
              subtitle: const Text('PNG Image • 142 KB',
                  style: TextStyle(fontSize: 12)),
              onTap: () {
                _addSampleAttachment('Screenshot_Log.png', 'image/png');
                Navigator.pop(context);
              },
            ),
            ListTile(
              leading: const CircleAvatar(
                  backgroundColor: AppColors.primaryLight,
                  child: Icon(Icons.picture_as_pdf, color: AppColors.primary)),
              title: const Text('System_Report.pdf',
                  style: TextStyle(fontWeight: FontWeight.w600, fontSize: 14)),
              subtitle: const Text('PDF Document • 450 KB',
                  style: TextStyle(fontSize: 12)),
              onTap: () {
                _addSampleAttachment('System_Report.pdf', 'application/pdf');
                Navigator.pop(context);
              },
            ),
          ],
        ),
      ),
    );
  }

  Future<void> _postReply() async {
    final text = _replyController.text.trim();
    if (text.isEmpty && _pendingAttachments.isEmpty) return;

    setState(() => _isSubmitting = true);
    try {
      final repo = ref.read(ticketRepositoryProvider);
      await repo.sendReply(widget.ticket.id, text,
          attachments: _pendingAttachments);
      _replyController.clear();
      setState(() => _pendingAttachments.clear());
      ref.invalidate(ticketMessagesProvider(widget.ticket.id));
      if (mounted) {
        AppToast.showSuccess(context, 'Reply posted successfully');
      }
    } catch (e) {
      if (mounted) {
        AppToast.showError(context, 'Error posting reply. Please try again.');
      }
    } finally {
      if (mounted) setState(() => _isSubmitting = false);
    }
  }

  Future<void> _updateTicketStatusAndTech() async {
    try {
      final repo = ref.read(ticketRepositoryProvider);
      if (_selectedStatus != null && _selectedStatus != widget.ticket.status) {
        await repo.updateStatus(widget.ticket.id, _selectedStatus!);
      }
      if (_selectedTechnicianId != null) {
        await repo.assignTechnician(widget.ticket.id, _selectedTechnicianId!);
      }
      ref.invalidate(ticketDetailsProvider(widget.ticket.id));
      ref.invalidate(ticketsProvider);
      if (mounted) {
        AppToast.showSuccess(context, 'Ticket updated successfully');
      }
    } catch (e) {
      if (mounted) {
        AppToast.showError(context, 'Update failed. Please try again.');
      }
    }
  }

  void _showCannedResponsesBottomSheet() {
    final cannedAsync = ref.read(cannedResponsesProvider);
    cannedAsync.when(
      data: (responses) {
        if (responses.isEmpty) {
          AppToast.showInfo(context, 'No canned responses available');
          return;
        }
        showModalBottomSheet(
          context: context,
          shape: const RoundedRectangleBorder(
            borderRadius: BorderRadius.vertical(top: Radius.circular(AppRadius.modal)),
          ),
          builder: (context) => Container(
            padding: const EdgeInsets.all(AppSpacing.md),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Text('Select Canned Response',
                    style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
                const SizedBox(height: 12),
                Expanded(
                  child: ListView.builder(
                    itemCount: responses.length,
                    itemBuilder: (context, index) {
                      final item = responses[index];
                      final title = item['title']?.toString() ?? 'Template';
                      final content = item['content']?.toString() ?? '';
                      return ListTile(
                        title: Text(title, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14)),
                        subtitle: Text(content, maxLines: 2, overflow: TextOverflow.ellipsis, style: const TextStyle(fontSize: 12)),
                        onTap: () {
                          Navigator.pop(context);
                          final curText = _replyController.text;
                          _replyController.text = curText.isNotEmpty ? '$curText\n\n$content' : content;
                          AppToast.showInfo(context, 'Template inserted into reply composer.');
                        },
                      );
                    },
                  ),
                ),
              ],
            ),
          ),
        );
      },
      loading: () => AppToast.showInfo(context, 'Loading canned responses...'),
      error: (_, __) => AppToast.showError(context, 'Failed to load templates'),
    );
  }

  void _showReopenDialog() {
    final reasonController = TextEditingController();
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Re-open this ticket?', style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text('Tell the support team why the issue has not been resolved:', style: TextStyle(fontSize: 13, color: AppColors.textSecondary)),
            const SizedBox(height: 12),
            TextField(
              controller: reasonController,
              maxLines: 3,
              decoration: const InputDecoration(
                hintText: 'Enter reason...',
                border: OutlineInputBorder(),
              ),
            ),
          ],
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context),
            child: const Text('Cancel'),
          ),
          ElevatedButton(
            style: ElevatedButton.styleFrom(backgroundColor: AppColors.danger),
            onPressed: () async {
              final reason = reasonController.text.trim();
              if (reason.isEmpty) {
                AppToast.showError(context, 'Please enter a reason to reopen.');
                return;
              }
              final currentContext = context;
              Navigator.pop(currentContext);
              try {
                final repo = ref.read(ticketRepositoryProvider);
                await repo.reopenTicket(widget.ticket.id, reason);
                ref.invalidate(ticketDetailsProvider(widget.ticket.id));
                ref.invalidate(ticketsProvider);
                if (mounted) AppToast.showSuccess(this.context, 'Ticket reopened successfully');
              } catch (e) {
                if (mounted) AppToast.showError(this.context, 'Failed to reopen ticket');
              }
            },
            child: const Text('Confirm Re-open', style: TextStyle(color: Colors.white)),
          ),
        ],
      ),
    );
  }

  void _handleAcceptAndClose() {
    showCsatDialog(
      context: context,
      ticketNumber: widget.ticket.ticketNumber,
      onSubmit: (rating, comment) async {
        Navigator.pop(context);
        try {
          final repo = ref.read(ticketRepositoryProvider);
          await repo.updateStatus(widget.ticket.id, 'Closed');
          await repo.submitCsatRating(widget.ticket.id, rating, comment);
          ref.invalidate(ticketDetailsProvider(widget.ticket.id));
          ref.invalidate(ticketsProvider);
          if (mounted) AppToast.showSuccess(context, 'Thank you for your rating!');
        } catch (e) {
          if (mounted) AppToast.showError(context, 'Failed to submit rating');
        }
      },
      onSkip: () async {
        Navigator.pop(context);
        try {
          final repo = ref.read(ticketRepositoryProvider);
          await repo.updateStatus(widget.ticket.id, 'Closed');
          ref.invalidate(ticketDetailsProvider(widget.ticket.id));
          ref.invalidate(ticketsProvider);
          if (mounted) AppToast.showInfo(context, 'Ticket closed');
        } catch (e) {
          if (mounted) AppToast.showError(context, 'Failed to close ticket');
        }
      },
    );
  }

  void _showAiReplyAssistantModal() {
    showModalBottomSheet(
      context: context,
      shape: const RoundedRectangleBorder(
        borderRadius:
            BorderRadius.vertical(top: Radius.circular(AppRadius.modal)),
      ),
      builder: (context) => Container(
        padding: const EdgeInsets.all(AppSpacing.lg),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Row(
              children: [
                Icon(Icons.auto_awesome, color: Color(0xFF6366F1), size: 22),
                SizedBox(width: AppSpacing.sm),
                Text(
                  'AI Reply Assistant (Draft Only)',
                  style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
                ),
              ],
            ),
            const SizedBox(height: AppSpacing.xs),
            const Text(
              'Generates a draft reply for review. You must review and manually send.',
              style: TextStyle(color: AppColors.textSecondary, fontSize: 13),
            ),
            const SizedBox(height: AppSpacing.md),
            ListTile(
              leading: const CircleAvatar(
                backgroundColor: Color(0xFFEEF2FF),
                child: Icon(Icons.handshake_outlined, color: Color(0xFF6366F1)),
              ),
              title: const Text('Polite Acknowledgment',
                  style: TextStyle(fontWeight: FontWeight.bold, fontSize: 14)),
              subtitle: const Text(
                  'Acknowledge ticket receipt and confirm technician is investigating.',
                  style: TextStyle(fontSize: 12)),
              onTap: () => _generateAiDraft(
                  'generate', 'Acknowledge ticket receipt and confirm technician investigation'),
            ),
            ListTile(
              leading: const CircleAvatar(
                backgroundColor: Color(0xFFEEF2FF),
                child: Icon(Icons.help_outline, color: Color(0xFF6366F1)),
              ),
              title: const Text('Request Diagnostics',
                  style: TextStyle(fontWeight: FontWeight.bold, fontSize: 14)),
              subtitle: const Text(
                  'Ask employee for screenshots, OS version, or reproduction steps.',
                  style: TextStyle(fontSize: 12)),
              onTap: () => _generateAiDraft(
                  'generate', 'Ask user for system specifications, error logs, and screenshots'),
            ),
            ListTile(
              leading: const CircleAvatar(
                backgroundColor: Color(0xFFEEF2FF),
                child: Icon(Icons.check_circle_outline, color: Color(0xFF6366F1)),
              ),
              title: const Text('Provide Troubleshooting Steps',
                  style: TextStyle(fontWeight: FontWeight.bold, fontSize: 14)),
              subtitle: const Text(
                  'Draft step-by-step guidance to resolve the reported problem.',
                  style: TextStyle(fontSize: 12)),
              onTap: () => _generateAiDraft(
                  'generate', 'Provide clear troubleshooting and resolution instructions'),
            ),
          ],
        ),
      ),
    );
  }

  Future<void> _generateAiDraft(String action, String contextDesc) async {
    Navigator.pop(context);
    setState(() => _isSubmitting = true);
    AppToast.showInfo(context, 'AI is drafting response...');
    try {
      final repo = ref.read(aiRepositoryProvider);
      final reply = await repo.generateReply(widget.ticket.id,
          action: action, context: contextDesc);
      _replyController.text = reply.draft;
      if (mounted) {
        AppToast.showSuccess(
            context, 'AI draft inserted! Review and edit before sending.');
      }
    } catch (e) {
      if (mounted) {
        AppToast.showError(context, 'Failed to generate AI draft: $e');
      }
    } finally {
      if (mounted) setState(() => _isSubmitting = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final detailsAsync = ref.watch(ticketDetailsProvider(widget.ticket.id));
    final messagesAsync = ref.watch(ticketMessagesProvider(widget.ticket.id));
    final techniciansAsync =
        ref.watch(ticketTechniciansProvider(widget.ticket.id));
    final activitiesAsync =
        ref.watch(ticketActivitiesProvider(widget.ticket.id));
    final isDesktop = MediaQuery.of(context).size.width >= 768;
    final currentUser = ref.watch(currentUserProvider);
    final userRole = currentUser?.roleEntity?.name;
    final isStaff = userRole == 'ROLE_IT_TECH' ||
        userRole == 'ROLE_MAINTENANCE_TECH' ||
        userRole == 'ROLE_ADMIN' ||
        userRole == 'ROLE_HR';

    return Scaffold(
      backgroundColor: AppColors.bgApp,
      appBar: AppBar(
        title: Text(widget.ticket.ticketNumber),
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh),
            onPressed: () {
              ref.invalidate(ticketDetailsProvider(widget.ticket.id));
              ref.invalidate(ticketMessagesProvider(widget.ticket.id));
              ref.invalidate(ticketActivitiesProvider(widget.ticket.id));
            },
          ),
        ],
      ),
      body: SafeArea(
        child: SingleChildScrollView(
          padding: EdgeInsets.all(isDesktop ? AppSpacing.xl : AppSpacing.md),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // Ticket Header Card
              Container(
                padding: const EdgeInsets.all(AppSpacing.md),
                decoration: BoxDecoration(
                  color: AppColors.surface,
                  borderRadius: BorderRadius.circular(AppRadius.card),
                  border: Border.all(color: AppColors.border),
                  boxShadow: AppShadows.small,
                ),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Wrap(
                      alignment: WrapAlignment.spaceBetween,
                      crossAxisAlignment: WrapCrossAlignment.center,
                      spacing: 8,
                      runSpacing: 6,
                      children: [
                        Wrap(
                          spacing: 8,
                          runSpacing: 4,
                          crossAxisAlignment: WrapCrossAlignment.center,
                          children: [
                            Container(
                              padding: const EdgeInsets.symmetric(
                                  horizontal: AppSpacing.sm, vertical: 3),
                              decoration: BoxDecoration(
                                color: AppColors.primaryLight,
                                borderRadius:
                                    BorderRadius.circular(AppRadius.badge),
                              ),
                              child: Text(
                                widget.ticket.ticketNumber,
                                style: const TextStyle(
                                    color: AppColors.primary,
                                    fontWeight: FontWeight.bold,
                                    fontSize: 13),
                              ),
                            ),
                            PriorityBadge(priority: widget.ticket.priority),
                          ],
                        ),
                        StatusBadge(status: widget.ticket.status),
                      ],
                    ),
                    const SizedBox(height: AppSpacing.sm),
                    Text(
                      widget.ticket.title,
                      style: const TextStyle(
                          fontSize: 18,
                          fontWeight: FontWeight.bold,
                          color: AppColors.textPrimary),
                    ),
                  ],
                ),
              ),
              const SizedBox(height: AppSpacing.md),

              // Responsive Layout Grid
              if (isDesktop)
                Row(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Expanded(
                        flex: 2,
                        child: _buildMainChatColumn(
                            context, detailsAsync, messagesAsync, isStaff)),
                    const SizedBox(width: AppSpacing.lg),
                    Expanded(
                        flex: 1,
                        child: Column(
                          children: [
                            if (isStaff) ...[
                              AiAssistantCard(
                                ticketId: widget.ticket.id,
                                onInsertDraft: (draft) {
                                  _replyController.text = draft;
                                  AppToast.showSuccess(context,
                                      'Draft inserted into composer!');
                                },
                              ),
                              const SizedBox(height: AppSpacing.md),
                            ],
                            _buildSidebarInfoColumn(context, detailsAsync,
                                techniciansAsync, activitiesAsync),
                          ],
                        )),
                  ],
                )
              else
                Column(
                  children: [
                    if (isStaff) ...[
                      AiAssistantCard(
                        ticketId: widget.ticket.id,
                        onInsertDraft: (draft) {
                          _replyController.text = draft;
                          AppToast.showSuccess(
                              context, 'Draft inserted into composer!');
                        },
                      ),
                      const SizedBox(height: AppSpacing.md),
                    ],
                    _buildSidebarInfoColumn(context, detailsAsync,
                        techniciansAsync, activitiesAsync),
                    // SLA Tracking Card
                    detailsAsync.maybeWhen(
                      data: (data) {
                        final slaData = data['sla'];
                        final sla = widget.ticket.sla ??
                            (slaData != null
                                ? TicketSla.fromJson(slaData)
                                : null);
                        if (sla != null) {
                          return Padding(
                            padding:
                                const EdgeInsets.only(top: AppSpacing.md),
                            child: SlaCard(sla: sla),
                          );
                        }
                        return const SizedBox();
                      },
                      orElse: () => widget.ticket.sla != null
                          ? Padding(
                              padding:
                                  const EdgeInsets.only(top: AppSpacing.md),
                              child: SlaCard(sla: widget.ticket.sla!),
                            )
                          : const SizedBox(),
                    ),
                    const SizedBox(height: AppSpacing.md),
                    _buildMainChatColumn(
                        context, detailsAsync, messagesAsync, isStaff),
                  ],
                ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildMainChatColumn(
    BuildContext context,
    AsyncValue<Map<String, dynamic>> detailsAsync,
    AsyncValue<List<Map<String, dynamic>>> messagesAsync,
    bool isStaff,
  ) {
    final currentUser = ref.watch(currentUserProvider);
    final currentEmail = currentUser?.email.toLowerCase().trim() ?? '';
    final currentFullName = currentUser?.fullName.toLowerCase().trim() ?? '';

    bool isMessageFromMe(String? senderEmail, String? senderName) {
      if (senderEmail != null &&
          senderEmail.isNotEmpty &&
          currentEmail.isNotEmpty) {
        if (senderEmail.toLowerCase().trim() == currentEmail) return true;
      }
      if (senderName != null &&
          senderName.isNotEmpty &&
          currentFullName.isNotEmpty) {
        if (senderName.toLowerCase().trim() == currentFullName) return true;
      }
      return false;
    }

    return Container(
      padding: const EdgeInsets.all(AppSpacing.md),
      decoration: BoxDecoration(
        color: AppColors.surface,
        borderRadius: BorderRadius.circular(AppRadius.card),
        border: Border.all(color: AppColors.border),
        boxShadow: AppShadows.small,
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Row(
            children: [
              Icon(Icons.forum_outlined, size: 18, color: AppColors.primary),
              SizedBox(width: AppSpacing.sm),
              Text(
                'TICKET CONVERSATION',
                style: TextStyle(
                    fontSize: 12,
                    fontWeight: FontWeight.bold,
                    color: AppColors.textSecondary,
                    letterSpacing: 0.5),
              ),
            ],
          ),
          const SizedBox(height: AppSpacing.sm),
          const Divider(color: AppColors.border),
          const SizedBox(height: AppSpacing.md),

          // Main Description (First Message Card)
          detailsAsync.when(
            data: (details) {
              final description = details['description']?.toString() ??
                  widget.ticket.description;
              final createdBy = details['createdBy']?.toString() ??
                  widget.ticket.createdBy ??
                  'Ticket Creator';
              final isMe = isMessageFromMe(null, createdBy);

              return _buildMessageCard(
                senderName: createdBy,
                role: 'Ticket Owner',
                messageText: description,
                timeText:
                    DateFormat('dd MMM, HH:mm').format(widget.ticket.createdAt),
                isMe: isMe,
                attachments: details['attachments'] ?? [],
              );
            },
            loading: () => const Center(child: CircularProgressIndicator()),
            error: (e, _) => Text('Error: $e'),
          ),

          // Messages Timeline
          messagesAsync.when(
            data: (messages) {
              if (messages.isEmpty) return const SizedBox();
              return ListView.separated(
                shrinkWrap: true,
                physics: const NeverScrollableScrollPhysics(),
                itemCount: messages.length,
                separatorBuilder: (context, index) =>
                    const SizedBox(height: AppSpacing.md),
                itemBuilder: (context, index) {
                  final msg = messages[index];
                  final senderEmail = msg['senderEmail']?.toString();
                  final senderName = msg['sender']?.toString() ??
                      msg['senderName']?.toString() ??
                      'User';
                  final senderRole =
                      msg['senderRole']?.toString() ?? 'Support Staff';
                  final text = msg['message']?.toString() ??
                      msg['messageText']?.toString() ??
                      '';
                  final createdAt = msg['createdAt']?.toString() ?? '';
                  final isMe = isMessageFromMe(senderEmail, senderName);

                  return _buildMessageCard(
                    senderName: senderName,
                    role: senderRole,
                    messageText: text,
                    timeText: createdAt,
                    isMe: isMe,
                    attachments: msg['attachments'] ?? [],
                  );
                },
              );
            },
            loading: () => const SizedBox(),
            error: (_, __) => const SizedBox(),
          ),

          const SizedBox(height: AppSpacing.lg),
          const Divider(color: AppColors.border),
          const SizedBox(height: AppSpacing.md),

          // Reply Composer Dock
          if (_pendingAttachments.isNotEmpty) ...[
            Wrap(
              spacing: 8,
              children: _pendingAttachments.map((att) {
                return Chip(
                  avatar: const Icon(Icons.attach_file, size: 14),
                  label: Text(att['fileName'] ?? '',
                      style: const TextStyle(fontSize: 12)),
                  onDeleted: () {
                    setState(() => _pendingAttachments.remove(att));
                  },
                );
              }).toList(),
            ),
            const SizedBox(height: AppSpacing.sm),
          ],

          Row(
            children: [
              IconButton(
                icon: const Icon(Icons.attach_file,
                    color: AppColors.textSecondary),
                onPressed: _showAddAttachmentModal,
                tooltip: 'Attach file',
              ),
              IconButton(
                icon: const Icon(Icons.bolt_rounded,
                    color: AppColors.primary),
                onPressed: _showCannedResponsesBottomSheet,
                tooltip: 'Canned responses',
              ),
              if (isStaff)
                IconButton(
                  icon: const Icon(Icons.auto_awesome,
                      color: Color(0xFF6366F1)),
                  onPressed: _showAiReplyAssistantModal,
                  tooltip: 'AI Reply Assistant (Draft)',
                ),
              Expanded(
                child: AppInput(
                  placeholder: 'Write a response...',
                  controller: _replyController,
                  enabled: !_isSubmitting,
                ),
              ),
              const SizedBox(width: AppSpacing.sm),
              AppButton(
                text: 'Send',
                icon: Icons.send,
                isLoading: _isSubmitting,
                onPressed: _postReply,
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildMessageCard({
    required String senderName,
    required String role,
    required String messageText,
    required String timeText,
    required bool isMe,
    required List<dynamic> attachments,
  }) {
    final initial = senderName.isNotEmpty ? senderName.substring(0, 1) : 'U';

    return Align(
      alignment: isMe ? Alignment.centerRight : Alignment.centerLeft,
      child: Container(
        constraints: const BoxConstraints(maxWidth: 420),
        margin: const EdgeInsets.only(bottom: AppSpacing.sm),
        padding: const EdgeInsets.all(AppSpacing.md),
        decoration: BoxDecoration(
          color: isMe ? AppColors.primary : AppColors.surface,
          borderRadius: BorderRadius.only(
            topLeft: const Radius.circular(AppRadius.card),
            topRight: const Radius.circular(AppRadius.card),
            bottomLeft: isMe
                ? const Radius.circular(AppRadius.card)
                : const Radius.circular(2),
            bottomRight: isMe
                ? const Radius.circular(2)
                : const Radius.circular(AppRadius.card),
          ),
          border: isMe ? null : Border.all(color: AppColors.border),
          boxShadow: AppShadows.small,
        ),
        child: Column(
          crossAxisAlignment:
              isMe ? CrossAxisAlignment.end : CrossAxisAlignment.start,
          children: [
            Row(
              mainAxisSize: MainAxisSize.min,
              children: [
                CircleAvatar(
                  radius: 12,
                  backgroundColor:
                      isMe ? Colors.white24 : AppColors.primaryLight,
                  child: Text(
                    initial,
                    style: TextStyle(
                        fontSize: 10,
                        fontWeight: FontWeight.bold,
                        color: isMe ? Colors.white : AppColors.primary),
                  ),
                ),
                const SizedBox(width: AppSpacing.xs),
                Flexible(
                  child: Text(
                    isMe ? 'You ($role)' : '$senderName ($role)',
                    overflow: TextOverflow.ellipsis,
                    style: TextStyle(
                      fontSize: 12,
                      fontWeight: FontWeight.bold,
                      color: isMe ? Colors.white : AppColors.textPrimary,
                    ),
                  ),
                ),
                const SizedBox(width: AppSpacing.sm),
                Text(
                  timeText,
                  style: TextStyle(
                      fontSize: 10,
                      color: isMe ? Colors.white70 : AppColors.textSecondary),
                ),
              ],
            ),
            const SizedBox(height: AppSpacing.xs),
            Text(
              messageText,
              style: TextStyle(
                fontSize: 14,
                color: isMe ? Colors.white : AppColors.textPrimary,
                height: 1.4,
              ),
            ),
            if (attachments.isNotEmpty) ...[
              const SizedBox(height: 8),
              Wrap(
                spacing: 8,
                runSpacing: 8,
                children: attachments.map<Widget>((att) {
                  final fileName = att['fileName']?.toString() ?? 'attachment';
                  final filePath = att['filePath']?.toString() ?? '';
                  final fullUrl = filePath.startsWith('http')
                      ? filePath
                      : '${AppConfig.baseUrl.replaceAll('/api/', '')}$filePath';
                  final ext = fileName.split('.').last.toLowerCase();
                  final isImg = ['jpg', 'jpeg', 'png', 'gif', 'webp'].contains(ext);

                  if (isImg) {
                    return InkWell(
                      onTap: () => openImageLightbox(context,
                          imageUrl: fullUrl, title: fileName),
                      child: ClipRRect(
                        borderRadius: BorderRadius.circular(8),
                        child: Container(
                          width: 80,
                          height: 60,
                          color: Colors.black12,
                          child: Image.network(
                            fullUrl,
                            fit: BoxFit.cover,
                            errorBuilder: (_, __, ___) => const Icon(
                                Icons.broken_image,
                                size: 24,
                                color: Colors.white70),
                          ),
                        ),
                      ),
                    );
                  }

                  return Container(
                    padding:
                        const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                    decoration: BoxDecoration(
                      color: isMe ? Colors.white24 : AppColors.primaryLight,
                      borderRadius: BorderRadius.circular(6),
                    ),
                    child: Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Icon(Icons.insert_drive_file_outlined,
                            size: 14,
                            color: isMe ? Colors.white : AppColors.primary),
                        const SizedBox(width: 4),
                        Text(
                          fileName,
                          style: TextStyle(
                            fontSize: 11,
                            fontWeight: FontWeight.w600,
                            color: isMe ? Colors.white : AppColors.primary,
                          ),
                        ),
                      ],
                    ),
                  );
                }).toList(),
              ),
            ],
          ],
        ),
      ),
    );
  }

  Widget _buildSidebarInfoColumn(
    BuildContext context,
    AsyncValue<Map<String, dynamic>> detailsAsync,
    AsyncValue<List<Map<String, dynamic>>> techniciansAsync,
    AsyncValue<Map<String, dynamic>> activitiesAsync,
  ) {
    final currentUser = ref.watch(currentUserProvider);
    final userRole = currentUser?.roleEntity?.name;
    final isStaff = userRole == 'ROLE_IT_TECH' ||
        userRole == 'ROLE_MAINTENANCE_TECH' ||
        userRole == 'ROLE_ADMIN' ||
        userRole == 'ROLE_HR';

    return Column(
      children: [
        Container(
          padding: const EdgeInsets.all(AppSpacing.md),
          decoration: BoxDecoration(
            color: AppColors.surface,
            borderRadius: BorderRadius.circular(AppRadius.card),
            border: Border.all(color: AppColors.border),
            boxShadow: AppShadows.small,
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              const Text('TICKET INFORMATION',
                  style: TextStyle(
                      fontSize: 12,
                      fontWeight: FontWeight.bold,
                      color: AppColors.textSecondary,
                      letterSpacing: 0.5)),
              const SizedBox(height: AppSpacing.sm),
              const Divider(color: AppColors.border),
              const SizedBox(height: AppSpacing.sm),
              _buildInfoRow('Category', widget.ticket.category),
              _buildInfoRow('Created By', widget.ticket.createdBy ?? 'N/A'),
              _buildInfoRow(
                  'Created At',
                  DateFormat('dd MMM yyyy, HH:mm')
                      .format(widget.ticket.createdAt)),
              _buildInfoRow(
                  'Assigned To', widget.ticket.assignedTo ?? 'Unassigned'),
              if (isStaff) ...[
                const SizedBox(height: AppSpacing.md),
                const Divider(color: AppColors.border),
                const SizedBox(height: AppSpacing.md),

                const Text('SUPPORT ACTIONS',
                    style: TextStyle(
                        fontSize: 12,
                        fontWeight: FontWeight.bold,
                        color: AppColors.textSecondary,
                        letterSpacing: 0.5)),
                const SizedBox(height: AppSpacing.sm),

                // Status Selector
                const Text('Update Status',
                    style: TextStyle(
                        fontSize: 11,
                        fontWeight: FontWeight.bold,
                        color: AppColors.textSecondary)),
                const SizedBox(height: 4),
                DropdownButtonFormField<String>(
                  isExpanded: true,
                  value: _statuses.contains(_selectedStatus)
                      ? _selectedStatus
                      : 'Open',
                  decoration: InputDecoration(
                    filled: true,
                    fillColor: AppColors.bgApp,
                    contentPadding:
                        const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                    border: OutlineInputBorder(
                        borderRadius: BorderRadius.circular(AppRadius.input),
                        borderSide: const BorderSide(color: AppColors.border)),
                  ),
                  items: _statuses
                      .map((s) => DropdownMenuItem(
                          value: s,
                          child: Text(s, style: const TextStyle(fontSize: 13))))
                      .toList(),
                  onChanged: (val) => setState(() => _selectedStatus = val),
                ),
                const SizedBox(height: AppSpacing.sm),

                // Assign Tech Selector
                techniciansAsync.when(
                  data: (techs) {
                    if (techs.isEmpty) return const SizedBox();
                    return Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        const Text('Assign Technician',
                            style: TextStyle(
                                fontSize: 11,
                                fontWeight: FontWeight.bold,
                                color: AppColors.textSecondary)),
                        const SizedBox(height: 4),
                        DropdownButtonFormField<int>(
                          isExpanded: true,
                          value: _selectedTechnicianId,
                          hint: const Text('Select Technician',
                              style: TextStyle(fontSize: 13)),
                          decoration: InputDecoration(
                            filled: true,
                            fillColor: AppColors.bgApp,
                            contentPadding: const EdgeInsets.symmetric(
                                horizontal: 12, vertical: 8),
                            border: OutlineInputBorder(
                                borderRadius:
                                    BorderRadius.circular(AppRadius.input),
                                borderSide:
                                    const BorderSide(color: AppColors.border)),
                          ),
                          items: techs
                              .map((t) => DropdownMenuItem<int>(
                                  value: t['id'] as int,
                                  child: Text(
                                      t['fullName']?.toString() ?? 'Tech',
                                      style: const TextStyle(fontSize: 13))))
                              .toList(),
                          onChanged: (val) =>
                              setState(() => _selectedTechnicianId = val),
                        ),
                      ],
                    );
                  },
                  loading: () => const SizedBox(),
                  error: (_, __) => const SizedBox(),
                ),
                const SizedBox(height: AppSpacing.md),

                AppButton(
                  text: 'Save Ticket Changes',
                  icon: Icons.save,
                  isFullWidth: true,
                  onPressed: _updateTicketStatusAndTech,
                ),
              ] else if (widget.ticket.status == 'Resolved') ...[
                const SizedBox(height: AppSpacing.md),
                const Divider(color: AppColors.border),
                const SizedBox(height: AppSpacing.md),
                const Text('TICKET RESOLUTION',
                    style: TextStyle(
                        fontSize: 12,
                        fontWeight: FontWeight.bold,
                        color: AppColors.textSecondary,
                        letterSpacing: 0.5)),
                const SizedBox(height: AppSpacing.xs),
                const Text(
                  'This ticket is marked as resolved. Accept the solution or reopen if the issue persists.',
                  style: TextStyle(fontSize: 12, color: AppColors.textSecondary),
                ),
                const SizedBox(height: AppSpacing.md),
                AppButton(
                  text: 'Accept & Close Ticket',
                  icon: Icons.check_circle_outline,
                  isFullWidth: true,
                  onPressed: _handleAcceptAndClose,
                ),
                const SizedBox(height: AppSpacing.xs),
                OutlinedButton.icon(
                  style: OutlinedButton.styleFrom(
                    foregroundColor: AppColors.danger,
                    side: const BorderSide(color: AppColors.danger),
                    minimumSize: const Size.fromHeight(44),
                  ),
                  icon: const Icon(Icons.refresh_rounded, size: 18),
                  label: const Text('Re-open Ticket', style: TextStyle(fontWeight: FontWeight.bold)),
                  onPressed: _showReopenDialog,
                ),
              ],
            ],
          ),
        ),
        const SizedBox(height: AppSpacing.md),

        // Activity Timeline Card
        _buildActivityTimelineCard(context, activitiesAsync),
      ],
    );
  }

  Widget _buildActivityTimelineCard(
      BuildContext context, AsyncValue<Map<String, dynamic>> activitiesAsync) {
    return AppCard(
      padding: const EdgeInsets.all(AppSpacing.md),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Row(
            children: [
              Icon(Icons.history, size: 18, color: AppColors.primary),
              SizedBox(width: AppSpacing.sm),
              Text(
                'ACTIVITY TIMELINE',
                style: TextStyle(
                    fontSize: 12,
                    fontWeight: FontWeight.bold,
                    color: AppColors.textSecondary,
                    letterSpacing: 0.5),
              ),
            ],
          ),
          const SizedBox(height: AppSpacing.sm),
          const Divider(color: AppColors.border),
          const SizedBox(height: AppSpacing.sm),
          activitiesAsync.when(
            data: (data) {
              final items = data['items'] as List<dynamic>? ?? [];
              if (items.isEmpty) {
                return const Padding(
                  padding: EdgeInsets.symmetric(vertical: AppSpacing.sm),
                  child: EmptyState(
                    title: 'No activity logs yet',
                    description:
                        'Timeline activities will appear automatically as updates occur.',
                    icon: Icons.history,
                  ),
                );
              }

              return ListView.separated(
                shrinkWrap: true,
                physics: const NeverScrollableScrollPhysics(),
                itemCount: items.length,
                separatorBuilder: (context, index) =>
                    const SizedBox(height: AppSpacing.sm),
                itemBuilder: (context, index) {
                  final act = items[index] as Map<String, dynamic>;
                  final eventType = act['eventType']?.toString() ?? '';
                  final desc = act['description']?.toString() ?? eventType;
                  final prevVal = act['previousValue']?.toString();
                  final newVal = act['newValue']?.toString();
                  final createdAt = act['createdAt']?.toString() ?? '';
                  final actorData = act['actor'] as Map<String, dynamic>?;
                  final actorName = actorData?['displayName']?.toString();
                  final actorRole = actorData?['role']?.toString();

                  IconData eventIcon;
                  Color eventColor;
                  switch (eventType) {
                    case 'ticket_created':
                      eventIcon = Icons.add_circle_outline;
                      eventColor = AppColors.primary;
                      break;
                    case 'status_changed':
                      eventIcon = Icons.swap_horiz_rounded;
                      eventColor = AppColors.info;
                      break;
                    case 'priority_changed':
                      eventIcon = Icons.flag_outlined;
                      eventColor = AppColors.warning;
                      break;
                    case 'ticket_assigned':
                    case 'ticket_reassigned':
                      eventIcon = Icons.person_add_alt_outlined;
                      eventColor = AppColors.success;
                      break;
                    case 'comment_added':
                      eventIcon = Icons.chat_bubble_outline_rounded;
                      eventColor = AppColors.textSecondary;
                      break;
                    case 'attachment_added':
                      eventIcon = Icons.attach_file_rounded;
                      eventColor = AppColors.primary;
                      break;
                    default:
                      eventIcon = Icons.radio_button_checked;
                      eventColor = AppColors.primary;
                  }

                  return Row(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Container(
                        padding: const EdgeInsets.all(4),
                        decoration: BoxDecoration(
                          color: eventColor.withOpacity(0.12),
                          shape: BoxShape.circle,
                        ),
                        child: Icon(eventIcon, size: 14, color: eventColor),
                      ),
                      const SizedBox(width: AppSpacing.sm),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              desc,
                              style: const TextStyle(
                                  fontWeight: FontWeight.bold,
                                  fontSize: 12,
                                  color: AppColors.textPrimary),
                            ),
                            if (prevVal != null || newVal != null) ...[
                              const SizedBox(height: 2),
                              Text(
                                '${prevVal != null ? "$prevVal → " : ""}${newVal ?? ""}',
                                style: TextStyle(
                                    fontSize: 11,
                                    fontWeight: FontWeight.bold,
                                    color: eventColor),
                              ),
                            ],
                            const SizedBox(height: 2),
                            Text(
                              '$createdAt${actorName != null ? " · $actorName" : ""}${actorRole != null ? " ($actorRole)" : ""}',
                              style: const TextStyle(
                                  fontSize: 10, color: AppColors.textSecondary),
                            ),
                          ],
                        ),
                      ),
                    ],
                  );
                },
              );
            },
            loading: () => const SkeletonLoader(width: double.infinity, height: 100),
            error: (e, _) => ErrorState(
              title: 'Failed to load timeline',
              error: e,
              onRetry: () =>
                  ref.invalidate(ticketActivitiesProvider(widget.ticket.id)),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildInfoRow(String label, String value) {
    return Padding(
      padding: const EdgeInsets.only(bottom: AppSpacing.sm),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(label,
              style: const TextStyle(
                  fontSize: 12.5, color: AppColors.textSecondary)),
          const SizedBox(width: 8),
          Flexible(
            child: Text(
              value,
              textAlign: TextAlign.right,
              overflow: TextOverflow.ellipsis,
              maxLines: 3,
              style: const TextStyle(
                  fontSize: 13,
                  fontWeight: FontWeight.bold,
                  color: AppColors.textPrimary),
            ),
          ),
        ],
      ),
    );
  }
}
