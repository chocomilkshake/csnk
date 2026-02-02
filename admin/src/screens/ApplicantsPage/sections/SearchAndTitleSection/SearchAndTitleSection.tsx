import {
  CalendarIcon,
  ChevronRightIcon,
  HomeIcon,
  HospitalIcon,
  MapPinIcon,
  SearchIcon,
} from "lucide-react";
import {
  Breadcrumb,
  BreadcrumbItem,
  BreadcrumbLink,
  BreadcrumbList,
  BreadcrumbPage,
  BreadcrumbSeparator,
} from "../../../../components/ui/breadcrumb";
import { Button } from "../../../../components/ui/button";

export const SearchAndTitleSection = (): JSX.Element => {
  return (
    <section className="flex flex-col w-full items-center relative">
      <div className="flex flex-col items-start gap-2.5 px-2.5 py-10 relative w-full bg-[#f9fbff] overflow-hidden">
        <div className="gap-px flex items-center justify-center relative w-full">
          <div className="flex-col max-w-[1296px] justify-center gap-[5px] flex items-center relative mx-auto">
            <Breadcrumb>
              <BreadcrumbList className="gap-2 flex items-center justify-center">
                <BreadcrumbItem>
                  <BreadcrumbLink href="/" className="flex items-center gap-2">
                    <HomeIcon className="w-4 h-4" />
                  </BreadcrumbLink>
                </BreadcrumbItem>
                <BreadcrumbSeparator>
                  <ChevronRightIcon className="w-3.5 h-3.5" />
                </BreadcrumbSeparator>
                <BreadcrumbItem>
                  <BreadcrumbPage className="font-body-extra-large-body-xl-regular font-[number:var(--body-extra-large-body-xl-regular-font-weight)] text-[#ff6264] text-[length:var(--body-extra-large-body-xl-regular-font-size)] tracking-[var(--body-extra-large-body-xl-regular-letter-spacing)] leading-[var(--body-extra-large-body-xl-regular-line-height)] [font-style:var(--body-extra-large-body-xl-regular-font-style)]">
                    Applicants
                  </BreadcrumbPage>
                </BreadcrumbItem>
              </BreadcrumbList>
            </Breadcrumb>

            <h1 className="font-heading-heading-1 font-[number:var(--heading-heading-1-font-weight)] text-[#2c2c2c] text-[length:var(--heading-heading-1-font-size)] text-center tracking-[var(--heading-heading-1-letter-spacing)] leading-[var(--heading-heading-1-line-height)] whitespace-nowrap [font-style:var(--heading-heading-1-font-style)]">
              Kasambahay Applicants
            </h1>
          </div>

          <img
            className="absolute top-[77px] left-[1185px] w-[25px] h-[25px]"
            alt="Shapes"
            src="/shapes-2.svg"
          />

          <img
            className="absolute -top-10 -left-2.5 w-[268px] h-[190px]"
            alt="Shapes"
          />
        </div>

        <div className="absolute top-[-70px] left-[1334px] w-[352px] h-[352px] bg-[#ff22264c] rounded-[176.21px] blur-[257px]" />

        <img
          className="absolute top-[311px] left-[232px] w-[590px] h-[590px]"
          alt="Shapes"
          src="/shapes-1.svg"
        />

        <div className="absolute top-[471px] left-[539px] w-[590px] h-[590px] rounded-[294.95px] blur-[257px] bg-[linear-gradient(180deg,rgba(255,255,255,0.8)_0%,rgba(127,183,255,0.8)_100%)]" />

        <div className="absolute top-[-130px] left-[313px] w-[590px] h-[590px] rounded-[294.95px] blur-[257px] bg-[linear-gradient(180deg,rgba(255,43,47,0)_0%,rgba(255,255,255,0)_100%)]" />

        <img
          className="absolute top-0 left-[1315px] w-[125px] h-[190px]"
          alt="Group"
        />

        <img
          className="absolute -top-0.5 left-[522px] w-[25px] h-[25px]"
          alt="Shapes"
          src="/shapes.svg"
        />
      </div>

      <div className="flex max-w-[860px] w-full items-center gap-2.5 p-4 -mt-[52px] relative mx-auto bg-white rounded-[70px] border-[none] before:content-[''] before:absolute before:inset-0 before:p-0.5 before:rounded-[70px] before:[background:linear-gradient(161deg,rgba(139,126,126,1)_0%,rgba(0,0,0,1)_100%)] before:[-webkit-mask:linear-gradient(#fff_0_0)_content-box,linear-gradient(#fff_0_0)] before:[-webkit-mask-composite:xor] before:[mask-composite:exclude] before:z-[1] before:pointer-events-none z-10">
        <div className="flex items-center gap-3 relative flex-1">
          <div className="flex flex-col w-[410px] items-start justify-center gap-2.5 px-4 py-2 relative border-r [border-right-style:solid] border-[#e6e8ee]">
            <div className="inline-flex items-center gap-2 relative">
              <HospitalIcon className="w-4 h-4 text-[#ff5053]" />
              <span className="[font-family:'Inter',Helvetica] font-normal text-[#ff5053] text-base tracking-[0] leading-6 whitespace-nowrap">
                SearchIcon for Doctors, Hospitals, Clinics
              </span>
            </div>
          </div>

          <div className="flex flex-col items-start gap-2.5 px-4 py-2 relative flex-1 border-r [border-right-style:solid] border-[#e6e8ee]">
            <div className="inline-flex items-center gap-2 relative">
              <MapPinIcon className="w-4 h-4 text-[#ff5053]" />
              <span className="[font-family:'Inter',Helvetica] font-normal text-[#ff5053] text-base tracking-[0] leading-6 whitespace-nowrap">
                Location
              </span>
            </div>
          </div>

          <div className="flex items-center gap-2 px-4 py-2 relative flex-1">
            <CalendarIcon className="w-4 h-4 text-[#ff5053]" />
            <span className="[font-family:'Inter',Helvetica] font-normal text-[#ff5053] text-base tracking-[0] leading-6 whitespace-nowrap">
              Date
            </span>
          </div>
        </div>

        <Button className="inline-flex items-center justify-center gap-2 px-4 py-2 bg-[#e64f52] hover:bg-[#d44447] rounded-[44px]">
          <SearchIcon className="w-3.5 h-3.5" />
          <span className="font-body-large-body-lg-medium font-[number:var(--body-large-body-lg-medium-font-weight)] text-white text-[length:var(--body-large-body-lg-medium-font-size)] text-center tracking-[var(--body-large-body-lg-medium-letter-spacing)] leading-[var(--body-large-body-lg-medium-line-height)] whitespace-nowrap [font-style:var(--body-large-body-lg-medium-font-style)]">
            SearchIcon
          </span>
        </Button>
      </div>
    </section>
  );
};
