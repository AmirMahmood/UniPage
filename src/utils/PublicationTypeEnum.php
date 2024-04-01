<?php

namespace UniPage\utils;

enum PublicationTypeEnum: string
{
    case CONFERENCE = "conference";
    case JOURNALS = "journal";
    case PHD_THESIS = "phdthesis";
    case MASTER_THESIS = "masterthesis";
    case UNPUBLISHED = "unpublished";
};
